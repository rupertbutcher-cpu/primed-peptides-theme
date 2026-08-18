"""
Pulls the live product catalogue (name, format, price, stock qty/status) from the
ChemResearch supplier ordering portal, via the already-logged-in Chrome session
(see chemresearch_login.py). No lead-time field exists anywhere in this portal
(checked Products + Dashboard) - only a binary in-stock/out-of-stock plus a
"notify me when back" signup, so that part of the original ask isn't available here.

Run chemresearch_login.py first and log in, then run this.
"""
import json
import re
from playwright.sync_api import sync_playwright

DEBUG_PORT = 9227


PRICE_RE = re.compile(r'^£[\d.]+$')


def parse_products(text):
    lines = [l.strip() for l in text.split('\n')]
    # Find where the product list actually starts - right after the format-filter counts
    start = 0
    for i, l in enumerate(lines):
        if l == 'Cartridges':
            start = i + 2  # skip the count line right after it too
            break
    products = []
    i = start
    while i < len(lines):
        if not lines[i]:
            i += 1
            continue
        name = lines[i]
        i += 1
        # description (may be empty lines before it) - but some products (e.g. blends
        # sold "per each" rather than "per cartridge") have NO description paragraph at
        # all and go straight from name to price. Peek ahead: if the next non-blank line
        # is itself a price, there's no description to consume. Found 2026-08-18 - without
        # this check every product after the first description-less one silently
        # misaligns (name/price/stock all shift into the wrong fields).
        while i < len(lines) and not lines[i]:
            i += 1
        if i < len(lines) and PRICE_RE.match(lines[i]):
            desc = ''
        else:
            desc = lines[i] if i < len(lines) else ''
            i += 1
            while i < len(lines) and not lines[i]:
                i += 1
        price_line = lines[i] if i < len(lines) else ''
        price = None
        m = re.match(r'£([\d.]+)', price_line)
        if m:
            price = float(m.group(1))
        i += 1
        fmt_line = lines[i] if i < len(lines) else ''
        fmt = 'cartridge' if 'cartridge' in fmt_line.lower() else 'vial'
        i += 1
        qty_line = lines[i] if i < len(lines) else ''
        i += 1
        status_line = lines[i] if i < len(lines) else ''
        i += 1
        action_line = lines[i] if i < len(lines) else ''
        i += 1

        if qty_line == 'Out of stock':
            qty = 0
            in_stock = False
        else:
            try:
                qty = int(qty_line.replace(',', ''))
                in_stock = True
            except ValueError:
                qty = None
                in_stock = status_line.lower() == 'in stock'

        products.append({
            'name': name,
            'description': desc,
            'price': price,
            'format': fmt,
            'qty': qty,
            'inStock': in_stock,
        })

        # Stop once we've consumed a clean multiple - safety valve against drift
        if len(products) >= 60:
            break
    return products


def main():
    with sync_playwright() as p:
        browser = p.chromium.connect_over_cdp(f"http://localhost:{DEBUG_PORT}")
        page = browser.contexts[0].pages[0]
        if 'products' not in page.url:
            page.goto('https://orders.chemresearch.co.uk/portal/products')
            page.wait_for_timeout(1500)
        text = page.evaluate("() => document.body.innerText")

    products = parse_products(text)
    print(f"Parsed {len(products)} products")
    with open('chemresearch_stock.json', 'w', encoding='utf-8') as f:
        json.dump(products, f, indent=2, ensure_ascii=False)
    print("Saved to chemresearch_stock.json")

    out_of_stock = [p for p in products if not p['inStock']]
    print(f"\n{len(out_of_stock)} out of stock:")
    for p in out_of_stock:
        print(f"  - {p['name']} ({p['format']})")


if __name__ == "__main__":
    main()
