"""
Open a real Chrome window on Amazon UK so accessory prices can be read over CDP.

WHY A REAL BROWSER
------------------
Amazon will not answer a plain request. curl with a normal browser user-agent gets HTTP 200
and a 2.3KB stub - not an error page, just no results, which is the failure mode that quietly
produces an empty price list rather than a visible error. A real Chrome window with no
automation flags gets the real page, which is the same approach already used for Booker (9224),
Bidfood (9225), Sessions (9226), Collins (9228) and Asahi (9231).

No sign-in is needed to read prices. Nothing is typed into this window by any script, and no
credential is read or written here.

Run: python amazon_open.py
Then: python amazon_accessory_prices.py
"""
import os
import subprocess
import time

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
DEBUG_PORT = 9232          # 9224 booker, 9225 bidfood, 9226 sessions, 9228 collins, 9231 asahi
START_URL = "https://www.amazon.co.uk/"
PROFILE_DIR = os.path.join(os.path.dirname(__file__), ".chrome-amazon-profile")


def main():
    os.makedirs(PROFILE_DIR, exist_ok=True)
    subprocess.Popen([
        CHROME_PATH,
        f"--remote-debugging-port={DEBUG_PORT}",
        f"--user-data-dir={PROFILE_DIR}",
        "--no-first-run",
        "--no-default-browser-check",
        START_URL,
    ])
    time.sleep(4)
    print("=" * 62)
    print("  Chrome is open on amazon.co.uk (debug port %d)." % DEBUG_PORT)
    print("  Accept the cookie banner if it appears - a banner overlay is")
    print("  enough to hide the price on a search result.")
    print("  Nothing else to do; prices can be read from here.")
    print("=" * 62)


if __name__ == "__main__":
    main()
