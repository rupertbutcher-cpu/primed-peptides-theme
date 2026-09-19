"""
Read Amazon UK search results for the five consumable accessories - price, image and link.

This GATHERS for review. It does not publish anything and it does not copy an image onto the
site; the thumbnails are pulled so the options can be compared in one place before Rupert
decides on sourcing. Amazon listing photos belong to the brand or the seller who uploaded
them, so nothing here should be deployed to primedpeptides.co.uk without settling that.

Attaches over CDP to the Chrome opened by amazon_open.py (port 9232). Amazon answers a plain
request with HTTP 200 and a 2.3KB stub rather than an error, so a scraper that does not use a
real browser produces an empty list that looks like "no results" instead of "blocked".

Usage:
    python amazon_open.py            # once, leave the window open
    python amazon_accessory_prices.py
"""
import base64
import json
import os
import re
import sys
import time
import urllib.parse
import urllib.request

from playwright.sync_api import sync_playwright

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, 'amazon_accessory_options.json')
CDP = 'http://127.0.0.1:9232'
MARKUP = 1.30

# The five products that currently have no photo on the site, with the search Rupert would
# realistically type. Kept as the site's product name -> search term so the results can be
# matched back to the card they are for.
SEARCHES = [
    ('Sharps Disposal Bin 1 Litre',           'sharps bin 1 litre'),
    ('Sterile Glass Vials 10ml - Pack of 10', '10ml sterile glass vials pack of 10'),
    ('Alcohol Prep Swabs - Pack of 200',      'alcohol prep swabs 200 pack'),
    ('Laboratory Syringes 1ml - Pack of 100', '1ml syringes pack of 100 luer'),
    ('Bacteriostatic Water 10ml',             'bacteriostatic water 10ml'),
    # NOT on the site at all yet. The Reusable Metal Pen is sold without anything to put on
    # the end of it, so this is a missing product rather than a missing photo.
    #
    # Rupert asked for "5mm x 0.5mm 32g". 32G is 0.23mm outer diameter - 0.5mm is 25G, which
    # is a far thicker needle. Searched on the gauge and the length, and the listing's own
    # stated diameter is kept in the title so the mismatch is visible rather than assumed away.
    ('Pen Needles 32G x 5mm',                 'pen needles 32g 5mm'),
    ('Pen Needles 32G x 4mm',                 'insulin pen needles 32g 4mm'),
]

RESULT = 'div[data-component-type="s-search-result"]'


def money(t):
    m = re.search(r'([\d,]+\.\d{2})', str(t or '').replace(chr(163), ''))
    return float(m.group(1).replace(',', '')) if m else None


def fetch_thumb(url):
    """Download a thumbnail and return it as a data URI, or None.

    Embedded rather than hotlinked because the review page has to render them, and a page
    that pulls images straight off Amazon's CDN shows nothing at all.
    """
    if not url:
        return None
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=20) as r:
            raw = r.read()
        if len(raw) > 400000:
            return None
        kind = 'jpeg' if url.lower().endswith(('.jpg', '.jpeg')) else 'png'
        return 'data:image/%s;base64,%s' % (kind, base64.b64encode(raw).decode('ascii'))
    except Exception:
        return None


def main():
    with sync_playwright() as p:
        try:
            b = p.chromium.connect_over_cdp(CDP, timeout=20000)
        except Exception as e:
            print('Cannot reach the Amazon Chrome on %s - run amazon_open.py first.' % CDP)
            print(str(e)[:120])
            sys.exit(1)
        pg = b.contexts[0].new_page()
        out = []
        try:
            for product, term in SEARCHES:
                url = 'https://www.amazon.co.uk/s?k=' + urllib.parse.quote_plus(term)
                pg.goto(url, wait_until='domcontentloaded', timeout=40000)
                time.sleep(3.0)
                try:
                    pg.wait_for_selector(RESULT, timeout=15000)
                except Exception:
                    pass
                cards = pg.eval_on_selector_all(RESULT, """els => els.slice(0, 6).map(e => {
                    const t = e.querySelector('h2');
                    const a = e.querySelector('h2 a') || e.querySelector('a.a-link-normal');
                    const pr = e.querySelector('.a-price .a-offscreen');
                    const im = e.querySelector('img.s-image');
                    const rv = e.querySelector('span.a-size-base.s-underline-text');
                    return {
                      asin: e.getAttribute('data-asin') || '',
                      title: t ? t.innerText.trim() : '',
                      href: a ? a.getAttribute('href') : '',
                      price: pr ? pr.textContent.trim() : '',
                      img: im ? im.getAttribute('src') : '',
                      reviews: rv ? rv.innerText.trim() : ''
                    };
                })""")
                keep = []
                for c in cards:
                    v = money(c['price'])
                    if not c['asin'] or not c['title'] or v is None:
                        continue
                    c['cost'] = v
                    c['sell_at_markup'] = round(v * MARKUP, 2)
                    c['url'] = ('https://www.amazon.co.uk' + c['href']) if c['href'].startswith('/') else c['href']
                    c['thumb'] = fetch_thumb(c['img'])
                    keep.append(c)
                    if len(keep) >= 4:
                        break
                print('%-42s %d option(s)' % (product[:42], len(keep)))
                for c in keep:
                    print('    %-9s %-52s cost %6.2f  ->  sell %6.2f'
                          % (c['asin'], c['title'][:52], c['cost'], c['sell_at_markup']))
                out.append({'product': product, 'search': term, 'options': keep})
                sys.stdout.flush()
        finally:
            pg.close()

    with open(OUT, 'w', encoding='utf-8') as f:
        json.dump({'markup': MARKUP, 'results': out}, f, indent=1, ensure_ascii=False)
    n = sum(len(r['options']) for r in out)
    print('\n%d options across %d products -> %s' % (n, len(out), OUT))
    if n == 0:
        print('NOTHING CAPTURED. Amazon shows a cookie banner on first visit and the overlay')
        print('hides the results - accept it in the Chrome window and re-run.')


if __name__ == '__main__':
    main()
