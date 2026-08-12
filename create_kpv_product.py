"""
One-off (12 Aug 2026): creates the standalone KPV 10mg Cartridge product on
primedpeptides.co.uk. Blocked for a while on a real price from Danny/ChemResearch
- Rupert confirmed cost (£10, ChemResearch) and sell price (£65) today.

Run once: python create_kpv_product.py
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

payload = {
    "name": "KPV 10mg Cartridge",
    "type": "simple",
    "status": "publish",
    "regular_price": "65",
    "description": (
        "<p>KPV (Lysine-Proline-Valine) is the C-terminal tripeptide fragment of "
        "\u03b1-melanocyte-stimulating hormone (\u03b1-MSH), retaining its parent "
        "compound's anti-inflammatory properties without the pigmentation-related "
        "effects. Research has focused on its activity across gut and skin pathways, "
        "including modulation of NF-\u03baB signalling and cytokine production.</p>\n"
        "<p>Studies suggest KPV may support gastrointestinal barrier function and "
        "skin inflammatory response, making it a frequent subject in research on "
        "inflammatory bowel conditions and dermatological applications.</p>\n"
        "<p><strong>Research applications:</strong> Anti-inflammatory mechanisms, "
        "gut barrier function, skin inflammatory pathways, NF-\u03baB modulation.</p>\n"
        "<p><em>For research use only. Not for human consumption.</em></p>\n"
    ),
    "short_description": (
        "<p>C-terminal tripeptide of \u03b1-MSH studied for anti-inflammatory "
        "activity across gut and skin pathways. 10mg stabilised cartridge.</p>\n"
    ),
    "manage_stock": False,
    "stock_status": "instock",
    "categories": [{"id": 16}],  # Recovery & Repair - matches BPC157+TB500's anti-inflammatory framing
}

qs = urllib.parse.urlencode({"consumer_key": CK, "consumer_secret": CS})
url = f"{BASE}/wp-json/wc/v3/products?{qs}"
body = json.dumps(payload).encode("utf-8")
req = urllib.request.Request(url, data=body, method="POST", headers={
    "Content-Type": "application/json",
    "User-Agent": "PrimedPeptides-Integration/1.0",
})
with urllib.request.urlopen(req, timeout=20) as resp:
    result = json.loads(resp.read())
    print(f"{resp.status} -> id={result['id']} name={result['name']!r} price={result['price']} permalink={result['permalink']}")
