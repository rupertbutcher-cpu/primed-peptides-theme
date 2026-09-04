"""Fills the Instagram email-signup form. Birthday widget renders all 3 listboxes'
options in the DOM at all times (only one set visible at a time), so options are
matched by exact trimmed textContent + visibility (offsetParent check) via JS,
not Playwright's text locators, to avoid hidden-duplicate ambiguity."""
import sys
from playwright.sync_api import sync_playwright

EMAIL = sys.argv[1]
PASSWORD = sys.argv[2]
MONTH = sys.argv[3]
DAY = sys.argv[4]
YEAR = sys.argv[5]
FULL_NAME = sys.argv[6]
USERNAME = sys.argv[7]

CLICK_VISIBLE_OPTION_JS = """(value) => {
    const opts = Array.from(document.querySelectorAll('[role="option"]'));
    const match = opts.find(o => o.textContent.trim() === value && o.offsetParent !== null);
    if (!match) return false;
    match.click();
    return true;
}"""

with sync_playwright() as p:
    browser = p.chromium.connect_over_cdp("http://localhost:9241")
    context = browser.contexts[0]
    page = context.pages[0]

    text_inputs = page.locator("input[type='text']")
    text_inputs.nth(0).fill(EMAIL)
    page.wait_for_timeout(300)

    page.locator("input[type='password']").fill(PASSWORD)
    page.wait_for_timeout(300)

    comboboxes = page.get_by_role("combobox")

    comboboxes.nth(0).click(force=True)
    page.wait_for_timeout(400)
    ok = page.evaluate(CLICK_VISIBLE_OPTION_JS, MONTH)
    print("month set:", ok)
    page.wait_for_timeout(400)

    comboboxes.nth(1).click(force=True)
    page.wait_for_timeout(400)
    ok = page.evaluate(CLICK_VISIBLE_OPTION_JS, DAY)
    print("day set:", ok)
    page.wait_for_timeout(400)

    comboboxes.nth(2).click(force=True)
    page.wait_for_timeout(400)
    ok = page.evaluate(CLICK_VISIBLE_OPTION_JS, YEAR)
    print("year set:", ok)
    page.wait_for_timeout(400)

    text_inputs.nth(1).fill(FULL_NAME)
    page.wait_for_timeout(300)

    page.locator("input[type='search']").fill(USERNAME)
    page.wait_for_timeout(800)

    page.screenshot(path=r"C:\Users\Rupert\AppData\Local\Temp\claude\c--Services\5fc02cb3-e561-429d-8d08-b81b91ab9ded\scratchpad\ig_filled3.png", full_page=True)
    print("Form filled, screenshot saved.")
