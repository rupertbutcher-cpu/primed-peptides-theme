"""Opens a real, visible Chrome window at 1st Formations, using a dedicated profile."""
import subprocess
import time
import os

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
DEBUG_PORT = 9248
HERE = os.path.dirname(os.path.abspath(__file__))
URL = "https://www.1stformations.co.uk/"


def main():
    profile_dir = os.path.join(HERE, ".chrome-firstformations-profile")
    os.makedirs(profile_dir, exist_ok=True)

    print(f"Opening Chrome at {URL} ...")
    subprocess.Popen([
        CHROME_PATH,
        f"--remote-debugging-port={DEBUG_PORT}",
        f"--user-data-dir={profile_dir}",
        "--no-first-run",
        "--no-default-browser-check",
        URL,
    ])
    time.sleep(4)
    print("Chrome is open.")


if __name__ == "__main__":
    main()
