"""
Opens a real, visible Chrome window to the ChemResearch supplier ordering portal.
Same pattern as den-restaurants/sessions_login.py, bidfood_login.py, booker_login.py -
real browser, no automation flags, so bot-protection never triggers. Rupert logs in
manually in this window; the session persists in PROFILE_DIR for later CDP automation
(reading stock/lead times) without needing to log in again each time.
"""
import subprocess
import time
import os

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
DEBUG_PORT = 9227
LOGIN_URL = "https://orders.chemresearch.co.uk/portal/products?format=cartridge"
PROFILE_DIR = os.path.join(os.path.dirname(__file__), ".chrome-chemresearch-profile")


def main():
    os.makedirs(PROFILE_DIR, exist_ok=True)
    print("Starting Chrome with remote debugging...")
    subprocess.Popen([
        CHROME_PATH,
        f"--remote-debugging-port={DEBUG_PORT}",
        f"--user-data-dir={PROFILE_DIR}",
        "--no-first-run",
        "--no-default-browser-check",
        LOGIN_URL,
    ])
    time.sleep(4)
    print("=" * 55)
    print("  Chrome is open at orders.chemresearch.co.uk")
    print("  Log in, then let Claude know.")
    print("=" * 55)


if __name__ == "__main__":
    main()
