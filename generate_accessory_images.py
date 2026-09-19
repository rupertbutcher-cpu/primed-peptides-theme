"""
Generate our own product photos for the consumable accessories.

WHY
---
The five consumables (and the pen needles, which are not on the site at all yet) had no
photo, so they fell through to a placeholder drawing. The obvious shortcut - lift the photo
from the Amazon listing we buy from - is not ours to take: those images belong to the brand
or the seller who uploaded them. So these are generated instead.

DELIBERATELY UNBRANDED. The prompts ask for plain packaging with no logos and no brand names.
These are bought-in generic consumables and the picture should not imply a brand we do not
stock, or invent a Primed Peptides own-label product that does not exist. A photo of the real
item, taken when stock lands, should replace these - a real image set in WooCommerce wins over
anything here without a code change.

House style is taken from the existing cartridge photos: soft ice-blue gradient studio
background, single subject centred, subtle drop shadow, square crop. That is the same look
described in PERPLEXITY_IMAGE_PROMPT.md, which until now was a prompt pasted into ChatGPT by
hand - this does the same job against the API so the set stays consistent and repeatable.

Usage:
    python generate_accessory_images.py --list
    python generate_accessory_images.py --only pen-needles
    python generate_accessory_images.py                 # all of them
"""
import base64
import json
import os
import sys
import time
import urllib.request

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HERE = os.path.dirname(os.path.abspath(__file__))
ENV_PATH = os.path.join(HERE, '.env')
OUT_DIR = os.path.join(HERE, 'generated-images', 'accessories')
MODEL = 'gpt-image-2'
SIZE = '1024x1024'

STYLE = (
    "Professional e-commerce product photography. Soft ice-blue gradient studio background "
    "(#eaf4fb to #ffffff), single subject centred, subtle soft drop shadow, even diffused "
    "lighting, sharp focus, high resolution, square 1:1 crop. Clean clinical pharmaceutical "
    "look. Plain unbranded packaging: no logos, no brand names, no trademarks, no invented "
    "company names, no readable paragraphs of text - only generic descriptive wording and "
    "size markings where a real product would carry them."
)

PRODUCTS = [
    ('sharps-bin', 'Sharps Disposal Bin 1 Litre',
     "A 1 litre UK-style clinical sharps disposal container. Bright yellow rigid plastic body "
     "with a slightly tapered shape, and a yellow snap-on lid with a rectangular slot aperture "
     "for needle entry. A plain white label panel on the front. Standing upright, closed."),

    ('glass-vials', 'Sterile Glass Vials 10ml - Pack of 10',
     "A group of ten empty 10ml clear borosilicate glass vials with silver aluminium crimp "
     "caps and grey rubber septa, arranged as a neat cluster - three or four standing at the "
     "front in focus and the rest behind them. Empty and clean, plain blank white label bands."),

    ('alcohol-swabs', 'Alcohol Prep Swabs - Pack of 200',
     "A small rectangular cardboard dispenser box of individually wrapped alcohol prep pads, "
     "standing closed, with two or three sealed square foil sachets resting on the surface "
     "beside it. Plain white and blue box with a simple generic label reading 'Alcohol Prep "
     "Pads 70% Isopropyl'."),

    ('syringes', 'Laboratory Syringes 1ml - Pack of 100',
     "Three or four 1ml disposable plastic laboratory syringes with clear barrels, visible "
     "volume graduation markings and blue plunger seals, laid on the surface slightly fanned "
     "out, needle caps on. Beside them a plain white box suggesting a pack of 100."),

    ('bac-water', 'Bacteriostatic Water 10ml',
     "A single 10ml clear glass vial filled with clear colourless liquid, silver aluminium "
     "crimp cap with a blue plastic flip-top centre, plain white label with simple generic "
     "wording. Standing upright, condensation-free, clean."),

    ('pen-needles', 'Pen Needles 32G x 5mm',
     "A small flat cardboard box of 100 disposable pen needles, plain white and blue, next to "
     "three individual sealed pen needle units standing upright - each a short screw-on plastic "
     "hub with a paper seal on top and a clear outer cap laid beside one of them to show the "
     "very fine short needle. Simple generic label wording '32G 5mm'."),
]


def load_key():
    env = {}
    with open(ENV_PATH, encoding='utf-8') as f:
        for ln in f:
            ln = ln.strip()
            if ln and '=' in ln and not ln.startswith('#'):
                k, v = ln.split('=', 1)
                env[k.strip()] = v.strip()
    key = env.get('OPENAI_API_KEY')
    if not key:
        print('No OPENAI_API_KEY in %s' % ENV_PATH)
        sys.exit(1)
    return key


def generate(key, slug, title, description):
    body = json.dumps({
        'model': MODEL,
        'prompt': '%s\n\n%s' % (description, STYLE),
        'size': SIZE,
        'n': 1,
    }).encode('utf-8')
    req = urllib.request.Request(
        'https://api.openai.com/v1/images/generations', data=body,
        headers={'Authorization': 'Bearer ' + key, 'Content-Type': 'application/json'})
    with urllib.request.urlopen(req, timeout=300) as r:
        d = json.load(r)
    item = d['data'][0]
    if item.get('b64_json'):
        raw = base64.b64decode(item['b64_json'])
    else:
        with urllib.request.urlopen(item['url'], timeout=120) as ir:
            raw = ir.read()
    path = os.path.join(OUT_DIR, '%s.png' % slug)
    with open(path, 'wb') as f:
        f.write(raw)
    return path, len(raw)


def main():
    if '--list' in sys.argv:
        for slug, title, _ in PRODUCTS:
            print('  %-14s %s' % (slug, title))
        return
    only = None
    if '--only' in sys.argv:
        only = sys.argv[sys.argv.index('--only') + 1]

    key = load_key()
    os.makedirs(OUT_DIR, exist_ok=True)
    todo = [p for p in PRODUCTS if only is None or p[0] == only]
    if not todo:
        print('No product matching --only %r. Use --list.' % only)
        sys.exit(1)

    ok = 0
    for slug, title, desc in todo:
        print('  %-14s generating...' % slug, end=' ')
        sys.stdout.flush()
        try:
            path, n = generate(key, slug, title, desc)
            print('%6.0f KB  -> %s' % (n / 1024, os.path.basename(path)))
            ok += 1
        except Exception as ex:
            # Say which one failed and carry on; a half-finished set is obvious and fixable,
            # a silent skip is what puts a placeholder back on the live site.
            print('FAILED %s' % str(ex)[:140])
        time.sleep(1)
    print('\n%d/%d generated -> %s' % (ok, len(todo), OUT_DIR))
    print('Review them before anything goes near the site.')


if __name__ == '__main__':
    main()
