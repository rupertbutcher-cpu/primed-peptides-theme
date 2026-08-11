"""
Cross-references Primed Peptides' live WooCommerce catalogue against Danny's
ChemResearch supplier stock pull (chemresearch_stock.json, built by
chemresearch_pull_stock.py) to flag two real risks:

1. Stock risk: a live/published WC product whose matching ChemResearch
   cartridge is currently out of stock at source.
2. Dosage mismatch: the dosage in the WC product title doesn't match what
   ChemResearch actually lists in that cartridge (same class of bug as the
   real Ipamorelin+CJC1295 "10mg+10mg" vs actual "5mg+5mg" error found and
   fixed 2026-08-09 - worth checking for more of these).

The WC <-> ChemResearch mapping is curated by hand below, not fuzzy-matched -
naming conventions differ enough between the two catalogues (e.g. WC's
"MOT-C Cartridge 10mg" vs ChemResearch's "MOTS-C 30mg Cartridge") that an
automatic name match would be unreliable for something that ends up deciding
whether a live product shows as sold out.

Usage:
    python chemresearch_wc_reconcile.py <path-to-wc-products.json>

wc-products.json is a raw dump of GET /wp-json/wc/v3/products (per_page=100)
against the Primed Peptides WooCommerce REST API - not committed to this repo
(contains no secrets itself, but is disk-fetched fresh each run to stay live).
"""
import json
import re
import sys

# wc_id -> chemresearch product name, or None if nothing in CR's catalogue
# plausibly matches (kept explicit so "no match" is a visible fact, not a
# silent gap).
WC_TO_CR = {
    47: "WOLVERINE TB-500 + BPC-157 10mg/10mg Cartridge",  # BPC157 + TB500 Cartridge 10mg+10mg - ChemResearch
                                                            # renamed this to add "WOLVERINE" on/before 2026-08-11
    30: "GHK-CU 100mg Cartridge",                     # GHK-CU Cartridge 100mg
    29: "MOTS-C 30mg Cartridge",                      # MOT-C Cartridge 10mg
    28: "IPAMORELIN + CJC 1295 NO DAC 5mg/5mg Cartridge",  # Ipamorelin + CJC1295 Cartridge 5mg+5mg
    27: "TESAMORELIN 20mg Cartridge",                 # Tesamorelin Cartridge 20mg
    26: "DSIP 10mg Cartridge",                        # DISP Cartridge 5mg
    24: "NAD+ 500mg Cartridge",                       # NAD+ Cartridge 1000mg
    23: "SEMAX 20mg Cartridge",                       # Semax Cartridge 20mg
    31: None,  # Kisspeptin Cartridge 5mg - CR only has KISSPEPTIN 10 5mg as a VIAL, no cartridge exists
    25: None,  # SS31 Cartridge 10mg - CR only has SS-31 10mg as a VIAL, no cartridge exists
    22: "SELANK 20mg Cartridge",                      # Selank Cartridge 20mg
    21: None,  # Wolverine Stack Cartridge 20mg - no CR item named "Wolverine"; CR's own
               # "Wolverine" nickname is actually used for the TB-500+BPC-157 cartridge (id 47's match)
               # - possible duplicate/naming confusion, not a confident match either way
    20: None,  # Recovery Pen 30mg - no clean single-peptide match; CR's GLOW 3-peptide
               # cartridge (TB-500+BPC-157+GHK-CU) is the closest candidate but not confident enough to assert
}

DOSE_RE = re.compile(r'(\d+(?:\.\d+)?)\s*mg', re.IGNORECASE)


def extract_doses(name):
    return [float(x) for x in DOSE_RE.findall(name)]


def main():
    if len(sys.argv) != 2:
        print("Usage: python chemresearch_wc_reconcile.py <wc-products.json>")
        sys.exit(1)

    with open(sys.argv[1], encoding='utf-8') as f:
        wc_products = {p['id']: p for p in json.load(f)}
    with open('chemresearch_stock.json', encoding='utf-8') as f:
        cr_by_name = {p['name']: p for p in json.load(f)}

    rows = []
    for wc_id, cr_name in WC_TO_CR.items():
        wc = wc_products.get(wc_id)
        if not wc:
            continue
        row = {
            'wc_id': wc_id,
            'wc_name': wc['name'],
            'wc_status': wc['status'],
            'wc_price': float(wc['price']) if wc['price'] else None,
            'cr_name': cr_name,
        }
        if cr_name:
            cr = cr_by_name.get(cr_name)
            if not cr:
                row['flag'] = 'CR_NAME_NOT_FOUND'
            else:
                row['cr_price'] = cr['price']
                row['cr_qty'] = cr['qty']
                row['cr_in_stock'] = cr['inStock']
                wc_doses = set(extract_doses(wc['name']))
                cr_doses = set(extract_doses(cr_name))
                row['dosage_mismatch'] = wc_doses != cr_doses
                row['stock_risk'] = (wc['status'] == 'publish') and not cr['inStock']
        else:
            row['flag'] = 'NO_MATCH_IN_CR_CATALOGUE'
        rows.append(row)

    with open('chemresearch_wc_reconcile.json', 'w', encoding='utf-8') as f:
        json.dump(rows, f, indent=2, ensure_ascii=False)

    print(f"{len(rows)} products reconciled -> chemresearch_wc_reconcile.json\n")
    for r in rows:
        flags = []
        if r.get('stock_risk'):
            flags.append('STOCK RISK (live, source out of stock)')
        if r.get('dosage_mismatch'):
            flags.append('DOSAGE MISMATCH')
        if r.get('flag'):
            flags.append(r['flag'])
        flag_str = f" -- {', '.join(flags)}" if flags else ''
        print(f"[{r['wc_id']}] {r['wc_name']} ({r['wc_status']}){flag_str}")


if __name__ == '__main__':
    main()
