import sys
from playwright.sync_api import sync_playwright

WAIT_MS = int(sys.argv[1]) if len(sys.argv) > 1 else 2000
OUT = sys.argv[2] if len(sys.argv) > 2 else r"C:\Users\Rupert\AppData\Local\Temp\claude\c--Services\5fc02cb3-e561-429d-8d08-b81b91ab9ded\scratchpad\ig_wait.png"

with sync_playwright() as p:
    browser = p.chromium.connect_over_cdp("http://localhost:9241")
    context = browser.contexts[0]
    page = context.pages[0]
    page.wait_for_timeout(WAIT_MS)
    page.screenshot(path=OUT, full_page=True)
    print("saved", OUT, "url:", page.url)
