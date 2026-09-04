"""CDP-based screenshot for 1st Formations, bypassing Playwright's own
page.screenshot() which reports 'Cannot take screenshot with 0 width' here."""
import sys
import base64
from playwright.sync_api import sync_playwright

PORT = sys.argv[1]
OUT = sys.argv[2]

with sync_playwright() as p:
    browser = p.chromium.connect_over_cdp(f"http://localhost:{PORT}")
    page = browser.contexts[0].pages[0]
    cdp = page.context.new_cdp_session(page)
    result = cdp.send("Page.captureScreenshot", {"format": "png"})
    with open(OUT, "wb") as f:
        f.write(base64.b64decode(result["data"]))
    print("saved", OUT)
