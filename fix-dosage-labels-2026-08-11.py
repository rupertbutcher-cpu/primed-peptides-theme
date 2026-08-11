"""
One-off fix (11 Aug 2026): corrects 3 product titles/descriptions on
primedpeptides.co.uk to match what ChemResearch (Danny's supplier) actually
lists for that cartridge - found via chemresearch_wc_reconcile.py.

  MOT-C  10mg  -> 30mg
  DISP   5mg   -> 10mg
  NAD+   1000mg -> 500mg

Run once: python fix-dosage-labels-2026-08-11.py
Reads WooCommerce keys from .env in this folder - never hardcode them here.
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

UPDATES = {
    29: {
        "name": "MOT-C Cartridge 30mg",
        "short_description": "<p>Mitochondria-targeting peptide studied for energy utilisation and physical endurance. 30mg stabilised cartridge.</p>\n",
    },
    26: {
        "name": "DISP Cartridge 10mg",
        "short_description": "<p>Peptide formulation studied for sleep onset and sleep depth improvement. 10mg stabilised cartridge.</p>\n",
        "description": "<p>This stabilised 10mg formulation is studied for its role in modulating GABAergic pathways and endogenous sleep regulation. Research suggests it may reduce sleep onset latency and improve deep sleep architecture, supporting more restorative rest cycles.</p>\n<p><strong>Research applications:</strong> Sleep onset, sleep architecture, GABAergic modulation, circadian rhythm support.</p>\n<p><em>For research use only. Not for human consumption.</em></p>\n",
    },
    24: {
        "name": "NAD+ Cartridge 500mg",
        "short_description": "<p>Nicotinamide adenine dinucleotide precursor studied for cellular energy and anti-aging mechanisms. 500mg stabilised cartridge.</p>\n",
    },
}

for pid, payload in UPDATES.items():
    qs = urllib.parse.urlencode({"consumer_key": CK, "consumer_secret": CS})
    url = f"{BASE}/wp-json/wc/v3/products/{pid}?{qs}"
    body = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(url, data=body, method="PUT", headers={
        "Content-Type": "application/json",
        "User-Agent": "PrimedPeptides-Integration/1.0",
    })
    with urllib.request.urlopen(req, timeout=20) as resp:
        result = json.loads(resp.read())
        print(f"[{pid}] {resp.status} -> name now: {result['name']!r}")
