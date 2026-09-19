"""
Put the generated accessory photos on the live products, and create the pen needles product.

TWO STEPS, because the WooCommerce API cannot accept image bytes - it takes a URL and
sideloads it. So each PNG is first uploaded by FTP into the theme's images/ folder (the same
path the site already serves product-cartridge.svg from), and the API is then pointed at that
public URL. WordPress copies it into the media library, after which the theme file is only a
staging copy.

DRY BY DEFAULT. Nothing uploads and nothing is written to the shop without --live.

Prices: the five existing accessories are NOT repriced. They already sell at 1.4x to 3.8x the
Amazon cost, so applying a 30% markup to them would be a price CUT - the syringes would drop
from GBP 30 to GBP 10.39. Only the new pen needles line needs a price, and Primed prices are
always whole multiples of GBP 5, rounded up.

Usage:
    python apply_accessory_images.py                      # dry run
    python apply_accessory_images.py --live               # images only
    python apply_accessory_images.py --live --create-needles 40
"""
import base64
import ftplib
import json
import os
import sys
import urllib.parse
import urllib.request

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HERE = os.path.dirname(os.path.abspath(__file__))
IMG_DIR = os.path.join(HERE, 'generated-images', 'accessories')
REMOTE_THEME = '/primedpeptides.co.uk/public_html/wp-content/themes/primed-peptides-theme-1'
PUBLIC_BASE = 'https://primedpeptides.co.uk/wp-content/themes/primed-peptides-theme-1/images'

# slug -> live WooCommerce product id (read from the API on 19 Sep)
MAP = {
    'sharps-bin':    (151, 'Sharps Disposal Bin 1 Litre'),
    'glass-vials':   (150, 'Sterile Glass Vials 10ml — Pack of 10'),
    'alcohol-swabs': (149, 'Alcohol Prep Swabs — Pack of 200'),
    'syringes':      (148, 'Laboratory Syringes 1ml — Pack of 100'),
    'bac-water':     (147, 'Bacteriostatic Water 10ml'),
}
NEEDLES_SLUG = 'pen-needles'


def env():
    d = {}
    with open(os.path.join(HERE, '.env'), encoding='utf-8') as f:
        for ln in f:
            ln = ln.strip()
            if ln and '=' in ln and not ln.startswith('#'):
                k, v = ln.split('=', 1)
                d[k.strip()] = v.strip()
    return d


def wc(e, method, path, payload=None):
    url = '%s/wp-json/wc/v3/%s' % (e['WOOCOMMERCE_SITE_URL'].rstrip('/'), path)
    auth = base64.b64encode(('%s:%s' % (e['WOOCOMMERCE_CONSUMER_KEY'],
                                        e['WOOCOMMERCE_CONSUMER_SECRET'])).encode()).decode()
    data = json.dumps(payload).encode('utf-8') if payload is not None else None
    req = urllib.request.Request(url, data=data, method=method, headers={
        'Authorization': 'Basic ' + auth, 'Content-Type': 'application/json'})
    with urllib.request.urlopen(req, timeout=120) as r:
        return json.load(r)


def upload(e, files):
    ftp = ftplib.FTP()
    ftp.connect(e['FTP_HOST'], int(e.get('FTP_PORT') or 21), timeout=60)
    ftp.login(e['FTP_USER'], e['FTP_PASS'])
    ftp.cwd(REMOTE_THEME + '/images')
    sent = []
    for local, remote in files:
        tmp = remote + '.uploading'
        with open(local, 'rb') as fh:
            ftp.storbinary('STOR ' + tmp, fh)
        try:
            ftp.delete(remote)
        except ftplib.error_perm:
            pass
        ftp.rename(tmp, remote)
        print('   sent %-28s %7.0f KB' % (remote, os.path.getsize(local) / 1024))
        sent.append(remote)
    ftp.quit()
    return sent


def main():
    live = '--live' in sys.argv
    needles_price = None
    if '--create-needles' in sys.argv:
        needles_price = sys.argv[sys.argv.index('--create-needles') + 1]

    e = env()
    jobs = []
    for slug, (pid, name) in MAP.items():
        p = os.path.join(IMG_DIR, '%s.png' % slug)
        if os.path.exists(p):
            jobs.append((slug, pid, name, p, 'accessory-%s.png' % slug))
        else:
            print('   MISSING %s.png - skipped' % slug)

    needles_img = os.path.join(IMG_DIR, '%s.png' % NEEDLES_SLUG)
    print('%d product image(s) to set%s' % (
        len(jobs), '; pen needles to CREATE at GBP %s' % needles_price if needles_price else ''))
    for slug, pid, name, path, remote in jobs:
        print('   %-14s id %-4s %-42s' % (slug, pid, name[:42]))
    if not live:
        print('\nDRY RUN - nothing uploaded, nothing changed. Add --live.')
        return

    print('\nuploading images by FTP:')
    files = [(p, r) for _, _, _, p, r in jobs]
    if needles_price and os.path.exists(needles_img):
        files.append((needles_img, 'accessory-pen-needles.png'))
    upload(e, files)

    print('\nsetting product images:')
    for slug, pid, name, path, remote in jobs:
        src = '%s/%s' % (PUBLIC_BASE, remote)
        try:
            r = wc(e, 'PUT', 'products/%d' % pid, {'images': [{'src': src, 'alt': name}]})
            got = r['images'][0]['src'].split('/')[-1] if r.get('images') else 'NONE'
            print('   OK  %-14s id %-4s -> %s' % (slug, pid, got))
        except Exception as ex:
            print('   !!  %-14s id %-4s %s' % (slug, pid, str(ex)[:90]))

    if needles_price:
        print('\ncreating the pen needles product:')
        payload = {
            'name': 'Pen Needles 32G x 5mm — Pack of 100',
            'type': 'simple',
            'status': 'publish',
            'regular_price': str(needles_price),
            'categories': [{'id': 33}],
            'short_description':
                '<p>Disposable 32G x 5mm pen needles for use with the Reusable Metal Pen. '
                'Pack of 100, individually sealed.</p>',
            'description':
                '<p>Ultra-fine 32 gauge (0.23mm) x 5mm disposable pen needles, compatible with '
                'the Primed Peptides Reusable Metal Pen. Each needle is individually sealed and '
                'single use.</p>\n'
                '<p>Pack of 100.</p>\n'
                '<p><em>For research use only. Not for human consumption.</em></p>',
            'images': [{'src': '%s/accessory-pen-needles.png' % PUBLIC_BASE,
                        'alt': 'Pen Needles 32G x 5mm, pack of 100'}],
        }
        try:
            r = wc(e, 'POST', 'products', payload)
            print('   OK  created id %s at GBP %s  -> %s' % (r['id'], r.get('price'), r.get('permalink')))
        except Exception as ex:
            print('   !!  create failed: %s' % str(ex)[:160])


if __name__ == '__main__':
    main()
