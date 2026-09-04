"""Connects to the already-open Instagram signup Chrome (CDP port 9241) and screenshots it."""
import sys
from playwright.sync_api import sync_playwright

OUT = sys.argv[1] if len(sys.argv) > 1 else r"C:\Users\Rupert\AppData\Local\Temp\claude\c--Services\5fc02cb3-e561-429d-8d08-b81b91ab9ded\scratchpad\ig_current.png"

with sync_playwright() as p:
    browser = p.chromium.connect_over_cdp("http://localhost:9241")
    context = browser.contexts[0]
    page = context.pages[0] if context.pages else context.new_page()
    page.wait_for_timeout(500)
    page.screenshot(path=OUT)
    print("Saved:", OUT)
    print("URL:", page.url)
    print("TITLE:", page.title())
