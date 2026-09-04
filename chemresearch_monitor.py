"""
Unattended ChemResearch stock monitor for Primed Peptides.

Launches a fresh, real (visible, non-headless - same anti-bot-detection
pattern as chemresearch_login.py) Chrome using the already-authenticated
session in .chrome-chemresearch-profile, pulls the live catalogue, and
WhatsApps Rupert only when one of the 8 confidently-mapped LIVE WooCommerce
products flips in-stock <-> out-of-stock at source.

Deliberately does NOT touch WooCommerce at all. Rupert's call (2026-08-11):
keep selling through a ChemResearch stockout and treat it like a backorder,
same as Haul & Store does - don't hide the product from the site. This
script is monitoring/alerting only.

Needs Chrome NOT already open on .chrome-chemresearch-profile - Chrome locks
the profile dir to one process at a time, so if chemresearch_login.py's
manual-login window is still open, this run will fail cleanly (see the
"already locked" message) rather than doing anything unexpected.

Meant to run on a schedule via Windows Task Scheduler - see
install-chemresearch-monitor-task.ps1 for the registration snippet
(admin PowerShell, run once on the server).
"""
import datetime
import json
import os
import subprocess
import sys
import time
import urllib.request

from playwright.sync_api import sync_playwright

from chemresearch_pull_stock import parse_products
from chemresearch_wc_reconcile import WC_TO_CR

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
DEBUG_PORT = 9227
URL = "https://orders.chemresearch.co.uk/portal/products?format=cartridge"
HERE = os.path.dirname(os.path.abspath(__file__))
PROFILE_DIR = os.path.join(HERE, ".chrome-chemresearch-profile")
STOCK_FILE = os.path.join(HERE, "chemresearch_stock.json")
LOG_FILE = os.path.join(HERE, "chemresearch_monitor.log")
HISTORY_FILE = os.path.join(HERE, "chemresearch_stock_history.jsonl")
HISTORY_STATE_FILE = os.path.join(HERE, "chemresearch_stock_history_state.json")


# Task Scheduler discards stdout/stderr entirely, so a failing run left zero trace anywhere —
# found 2026-08-14 debugging exactly this. Minimal persistent logging so a future failure is
# diagnosable without re-running by hand.
def log(msg):
    line = f"{datetime.datetime.now().isoformat(timespec='seconds')} {msg}"
    print(line)
    with open(LOG_FILE, "a", encoding="utf-8") as f:
        f.write(line + "\n")

BOT_SEND_URL = "http://localhost:3001/api/internal/send"
# Shared internal-API secret. Value lives in C:\Services\personal\internal-api.json, which is
# NOT git-tracked - it used to be hardcoded here and in five other files across five services
# (2026-09-04). Same value as before; the point is that it is no longer in source control.
def _internal_secret():
    import json
    try:
        with open(r"C:\Services\personal\internal-api.json", "r", encoding="utf-8") as f:
            return json.load(f)["secret"]
    except Exception as e:
        # No literal fallback on purpose - see the JS note. Fail loudly rather than shipping the
        # old secret in source control.
        print(r"[internal-secret] cannot read C:\Services\personal\internal-api.json: %s" % e)
        return None


BOT_SECRET = _internal_secret()
ALERT_TO = "447545451386"  # Rupert - same number health-monitor.js alerts

# wc_id -> short label for the alert message (the 8 confidently-mapped live products)
WC_LABELS = {
    47: "BPC157 + TB500 10mg+10mg (£100)",
    30: "GHK-CU 100mg (£70)",
    29: "MOT-C 10mg (£30)",
    28: "Ipamorelin + CJC1295 5mg+5mg (£70)",
    27: "Tesamorelin 20mg (£80)",
    26: "DISP 5mg (£30)",
    24: "NAD+ 1000mg (£150)",
    23: "Semax 20mg (£70)",
}

MIN_EXPECTED_PRODUCTS = 8  # sanity floor - cartridges (what Primed Peptides actually buys) has legitimately
# sat at 18 of the site's 51 total products for a couple of days (2026-08-16, per Rupert) - 30 was set from
# an unfiltered/all-categories count and false-alerted every run. A real logged-out page shows ~1 product
# (confirmed 2026-08-14), so 8 still catches that while giving room for cartridge stock to fluctuate.


def send_whatsapp(text):
    body = json.dumps({"secret": BOT_SECRET, "waId": ALERT_TO + "@c.us", "text": text}).encode()
    req = urllib.request.Request(BOT_SEND_URL, data=body, headers={"Content-Type": "application/json"}, method="POST")
    urllib.request.urlopen(req, timeout=15)


def load_previous():
    try:
        with open(STOCK_FILE, encoding="utf-8") as f:
            return {p["name"]: p for p in json.load(f)}
    except FileNotFoundError:
        return {}


def append_history(products, date_str):
    """Appends one row per product for today to the persistent JSONL log, so the
    in/out-of-stock pattern over time can be reported on later. Guarded against
    double-appending if the monitor gets run more than once on the same day."""
    try:
        with open(HISTORY_STATE_FILE, encoding="utf-8") as f:
            last_date = json.load(f).get("lastDate")
    except FileNotFoundError:
        last_date = None

    if last_date == date_str:
        log(f"History already logged for {date_str}, skipping duplicate append.")
        return

    with open(HISTORY_FILE, "a", encoding="utf-8") as f:
        for p in products:
            row = {"date": date_str, "name": p["name"], "inStock": p["inStock"],
                   "qty": p["qty"], "price": p["price"]}
            f.write(json.dumps(row, ensure_ascii=False) + "\n")

    with open(HISTORY_STATE_FILE, "w", encoding="utf-8") as f:
        json.dump({"lastDate": date_str}, f)

    log(f"History logged: {len(products)} products for {date_str}.")


def main():
    log("=== run start ===")
    previous = load_previous()

    # Real (non-headless) Chrome, same anti-bot-detection reasoning as chemresearch_login.py -
    # but this run is unattended, so the window is pushed off-screen rather than made headless.
    # Headless is a different, detectable code path (missing plugins, WebDriver flag, etc.);
    # off-screen keeps it a genuinely normal browser window, just not visible on the server's
    # physical/RDP display. Also --start-minimized (2026-08-30): the task runs in Rupert's own
    # interactive RDP session, and Windows can snap an off-screen window back on-screen when
    # that session reconnects - minimized keeps it out of the way even if that happens.
    proc = subprocess.Popen([
        CHROME_PATH,
        f"--remote-debugging-port={DEBUG_PORT}",
        f"--user-data-dir={PROFILE_DIR}",
        "--no-first-run",
        "--no-default-browser-check",
        "--window-position=-32000,-32000",
        "--window-size=1280,900",
        "--start-minimized",
        URL,
    ])
    time.sleep(5)

    text = None
    try:
        with sync_playwright() as p:
            browser = p.chromium.connect_over_cdp(f"http://localhost:{DEBUG_PORT}")
            page = browser.contexts[0].pages[0]
            if "products" not in page.url:
                page.goto(URL)
                page.wait_for_timeout(1500)
            text = page.evaluate("() => document.body.innerText")
    except Exception as e:
        log(f"Pull failed: {e}")
    finally:
        subprocess.run(["taskkill", "/F", "/T", "/PID", str(proc.pid)],
                        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

    if text is None:
        log("text is None after pull attempt — sending failure alert")
        send_whatsapp("ChemResearch stock check failed to load the portal (profile may be locked by an open Chrome window, or the session needs a fresh login).")
        sys.exit(1)

    products = parse_products(text)
    log(f"parse_products found {len(products)} products")

    if len(products) < MIN_EXPECTED_PRODUCTS:
        log(f"Below MIN_EXPECTED_PRODUCTS ({MIN_EXPECTED_PRODUCTS}) — sending failure alert. First 500 chars of page text: {text[:500]!r}")
        send_whatsapp(f"ChemResearch stock check only found {len(products)} products (expected ~44+) - likely logged out. Run chemresearch_login.py and log in again.")
        sys.exit(1)

    current = {p["name"]: p for p in products}

    changes = []
    for wc_id, cr_name in WC_TO_CR.items():
        if not cr_name or wc_id not in WC_LABELS:
            continue
        prev = previous.get(cr_name)
        now = current.get(cr_name)
        if not now:
            continue
        if prev and prev["inStock"] != now["inStock"]:
            direction = "back IN STOCK" if now["inStock"] else "now OUT OF STOCK"
            changes.append(f"- {WC_LABELS[wc_id]}: {direction} at ChemResearch (qty {now['qty']})")

    with open(STOCK_FILE, "w", encoding="utf-8") as f:
        json.dump(products, f, indent=2, ensure_ascii=False)

    append_history(products, datetime.date.today().isoformat())

    if changes:
        msg = ("ChemResearch stock change (Primed Peptides):\n" + "\n".join(changes) +
               "\n\nStill selling as normal on the site - treat like a backorder, nothing changed on WooCommerce.")
        send_whatsapp(msg)
        log("Alert sent:\n" + msg)
    else:
        log(f"No change vs last check. {len(products)} products pulled.")


if __name__ == "__main__":
    main()
