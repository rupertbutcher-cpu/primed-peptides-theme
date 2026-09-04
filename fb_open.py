"""Opens a real, visible Chrome window at Facebook's login page, using a dedicated
profile. Rupert logs in himself - his password is never typed by automation."""
import subprocess
import time
import os

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
DEBUG_PORT = 9245
HERE = os.path.dirname(os.path.abspath(__file__))
LOGIN_URL = "https://www.facebook.com/login"


def main():
    profile_dir = os.path.join(HERE, ".chrome-facebook-profile")
    os.makedirs(profile_dir, exist_ok=True)

    print(f"Opening Chrome at {LOGIN_URL} ...")
    subprocess.Popen([
        CHROME_PATH,
        f"--remote-debugging-port={DEBUG_PORT}",
        f"--user-data-dir={profile_dir}",
        "--no-first-run",
        "--no-default-browser-check",
        LOGIN_URL,
    ])
    time.sleep(4)
    print("Chrome is open.")


if __name__ == "__main__":
    main()
