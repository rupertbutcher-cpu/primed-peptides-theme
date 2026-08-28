"""
One-off (28 Aug 2026): mirrors the 2 Selfnamed white-label topical peptide
creams already added as drafts on premiumpeptide.uk (ids 77/78) onto
primedpeptides.co.uk as drafts too, in the Aesthetic & Skin Support category
(id 29, matches GHK-CU Cartridge's category). Same SKU convention as GHK-CU
(PP-AS-001) -> PP-AS-002/003. Price is a placeholder (Selfnamed's own
suggested retail, same as used on Premium) - needs a real margin decision
before publishing on either site.

Run once: python create_selfnamed_skincare_products.py
"""
import json
import urllib.request
import urllib.parse

ENV_PATH = r"C:\Services\primed_peptides\.env"
env = {}
with open(ENV_PATH) as f:
    for line in f:
        line = line.strip()
        if line and '=' in line:
            k, v = line.split('=', 1)
            env[k] = v

BASE = env['WOOCOMMERCE_SITE_URL']
CK = env['WOOCOMMERCE_CONSUMER_KEY']
CS = env['WOOCOMMERCE_CONSUMER_SECRET']
UA = "PrimedPeptides-Integration/1.0"

PRODUCTS = [
    {
        "name": "Peptide Ageless AM/PM Cream",
        "sku": "PP-AS-002",
        "price": "9.00",
        "description": (
            "<p>A dual-action, day-and-night moisturiser built around a clinical-grade "
            "peptide complex \u2014 the same active studied in our GHK-Cu research line, "
            "in a topical, everyday format.</p>\n"
            "<p>Formulated to support skin elasticity and reduce the visible appearance "
            "of fine lines and wrinkles, with balanced hydration and a lightweight "
            "texture that absorbs easily. Apply to clean skin morning and evening.</p>\n"
            "<p><strong>Key ingredients:</strong> Peptides, Vitamin C, Vitamin E, "
            "Hyaluronic Acid, Sodium PCA</p>\n"
            "<p><strong>Star features:</strong> Smoothes fine lines \u00b7 Hydrating \u00b7 "
            "Boosts elasticity</p>\n"
            "<p>Gluten free \u00b7 Nut free \u00b7 Vegan</p>\n"
        ),
        "short_description": (
            "<p>Dual-action AM/PM peptide moisturiser \u2014 smoothes fine lines, "
            "hydrates, and boosts elasticity.</p>\n"
        ),
    },
    {
        "name": "Peptide Age-Defying Eye Cream",
        "sku": "PP-AS-003",
        "price": "6.50",
        "description": (
            "<p>A targeted eye cream built around the same clinical-grade peptide "
            "complex as our Ageless AM/PM Cream, formulated specifically for the "
            "delicate eye area.</p>\n"
            "<p>Helps reduce the appearance of fine lines, wrinkles and crow's feet "
            "while supporting a firmer, smoother look where it shows first. "
            "Lightweight, comfortable texture that absorbs easily without heaviness "
            "\u2014 pairs naturally with serums and creams in the same routine. Apply "
            "gently around clean skin morning and evening.</p>\n"
            "<p><strong>Key ingredients:</strong> Peptides, Vitamin C, Vitamin E, "
            "Sodium PCA</p>\n"
            "<p><strong>Star features:</strong> Smoothes fine lines \u00b7 Hydrating</p>\n"
            "<p>Gluten free \u00b7 Nut free \u00b7 Vegan</p>\n"
        ),
        "short_description": (
            "<p>Targeted peptide eye cream \u2014 smoothes fine lines and hydrates the "
            "delicate eye area.</p>\n"
        ),
    },
]

for p in PRODUCTS:
    payload = {
        "name": p["name"],
        "type": "simple",
        "status": "draft",
        "regular_price": p["price"],
        "description": p["description"],
        "short_description": p["short_description"],
        "sku": p["sku"],
        "manage_stock": False,
        "stock_status": "instock",
        "categories": [{"id": 29}],  # Aesthetic & Skin Support
    }
    qs = urllib.parse.urlencode({"consumer_key": CK, "consumer_secret": CS})
    url = f"{BASE}/wp-json/wc/v3/products?{qs}"
    body = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(url, data=body, method="POST", headers={
        "Content-Type": "application/json",
        "User-Agent": UA,
    })
    with urllib.request.urlopen(req, timeout=20) as resp:
        result = json.loads(resp.read())
        print(f"{resp.status} -> id={result['id']} name={result['name']!r} sku={result['sku']} status={result['status']}")
