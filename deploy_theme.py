"""
Deploy changed theme files over FTP to either peptide site.

  python deploy_theme.py primed  functions.php faq.php articles.php
  python deploy_theme.py premium functions.php articles.php index.php
  python deploy_theme.py primed  --all      (every .php + style.css in the repo root)
  python deploy_theme.py primed  --check footer.php functions.php
        UPLOADS NOTHING: downloads the live copy of each file and says SAME / DIFFERENT from the local one (line
        endings ignored), saving the live copies under %TEMP%\\live-theme-<site>. Run it before editing: the local
        functions.php has been stale against the live site before (Aug 2026) and an upload would have reverted it.

Uploads to a .tmp name first and renames into place, so a dropped connection
can never leave a half-written functions.php live on the site - which would
white-screen the whole shop.
"""

import ftplib
import os
import sys

# NOTE: the FTP account's root is one level ABOVE public_html - it lands on a
# folder named after the domain. Paths must include that, or every cwd 550s.
SITES = {
    'primed': {
        'env':  r'C:\Services\primed_peptides\.env',
        'local': r'C:\Services\primed_peptides',
        'remote': '/primedpeptides.co.uk/public_html/wp-content/themes/primed-peptides-theme-1',
        'themes_base': '/primedpeptides.co.uk/public_html/wp-content/themes',
    },
    'premium': {
        'env':  r'C:\Services\premium_peptide\.env',
        'local': r'C:\Services\premium_peptide',
        'remote': '/premiumpeptide.uk/public_html/wp-content/themes/premium-peptide',
        'themes_base': '/premiumpeptide.uk/public_html/wp-content/themes',
    },
}


def load_env(path):
    d = {}
    for ln in open(path, encoding='utf-8'):
        if '=' in ln and not ln.strip().startswith('#'):
            k, v = ln.split('=', 1)
            d[k.strip()] = v.strip()
    return d


def find_theme_dir(ftp, preferred, base):
    """Confirm the theme path, or list what's actually there so a wrong guess is obvious."""
    try:
        ftp.cwd(preferred)
        return preferred
    except ftplib.error_perm:
        pass
    try:
        ftp.cwd(base)
        names = ftp.nlst()
        print(f'  theme dir {preferred} not found. Available under {base}:')
        for n in names:
            print('    ', n)
    except ftplib.error_perm as e:
        print('  could not list themes dir:', e)
    return None


def main():
    if len(sys.argv) < 3:
        print(__doc__)
        return 1
    site = sys.argv[1].lower()
    if site not in SITES:
        print('site must be one of:', ', '.join(SITES))
        return 1
    cfg = SITES[site]
    env = load_env(cfg['env'])

    files = sys.argv[2:]
    check = '--check' in files
    files = [f for f in files if f != '--check']
    if files == ['--all']:
        files = sorted(f for f in os.listdir(cfg['local'])
                       if f.endswith('.php') or f == 'style.css')

    print(f"Connecting to {env['FTP_HOST']} as {env['FTP_USER']}...")
    ftp = ftplib.FTP()
    ftp.connect(env['FTP_HOST'], int(env.get('FTP_PORT') or 21), timeout=60)
    ftp.login(env['FTP_USER'], env['FTP_PASS'])
    print('  connected')

    remote = find_theme_dir(ftp, cfg['remote'], cfg['themes_base'])
    if not remote:
        ftp.quit()
        return 1
    print(f'  theme dir: {remote}\n')

    if check:
        import io
        out_dir = os.path.join(os.environ.get('TEMP', '.'), f'live-theme-{site}')
        os.makedirs(out_dir, exist_ok=True)
        norm = lambda b: b.replace(b'\r\n', b'\n')
        for f in files:
            buf = io.BytesIO()
            try:
                ftp.retrbinary(f'RETR {f}', buf.write)
            except ftplib.all_errors as e:
                print(f'  cannot read live {f}: {e}')
                continue
            live = buf.getvalue()
            local = os.path.join(cfg['local'], f)
            loc = open(local, 'rb').read() if os.path.exists(local) else None
            with open(os.path.join(out_dir, f), 'wb') as fh:
                fh.write(live)
            same = loc is not None and norm(live) == norm(loc)
            print(f'  {"SAME     " if same else "DIFFERENT"} {f:<22} live {len(live):>7,} bytes, local {len(loc or b""):>7,} bytes')
        print(f'\nlive copies saved in {out_dir} - nothing uploaded')
        ftp.quit()
        return 0

    sent = failed = 0
    for f in files:
        local = os.path.join(cfg['local'], f)
        if not os.path.exists(local):
            print(f'  SKIP (missing locally) {f}')
            continue
        size = os.path.getsize(local)
        tmp = f + '.uploading'
        try:
            with open(local, 'rb') as fh:
                ftp.storbinary(f'STOR {tmp}', fh)
            # Overwrite atomically-ish: delete target then rename.
            try:
                ftp.delete(f)
            except ftplib.error_perm:
                pass
            ftp.rename(tmp, f)
            print(f'  sent {f:<22} {size:>7,} bytes')
            sent += 1
        except ftplib.all_errors as e:
            print(f'  FAILED {f}: {e}')
            failed += 1
            try:
                ftp.delete(tmp)
            except ftplib.all_errors:
                pass

    print(f'\n{sent} uploaded, {failed} failed')
    ftp.quit()
    return 1 if failed else 0


if __name__ == '__main__':
    sys.exit(main())
