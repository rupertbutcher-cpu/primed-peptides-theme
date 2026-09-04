"""Opens a real, visible Chrome window at CircleLoop's signup page, using a
dedicated profile."""
import subprocess
import time
import os

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
DEBUG_PORT = 9246
HERE = os.path.dirname(os.path.abspath(__file__))
SIGNUP_URL = "https://www.circleloop.com/signup/"


def main():
    profile_dir = os.path.join(HERE, ".chrome-circleloop-profile")
    os.makedirs(profile_dir, exist_ok=True)

    print(f"Opening Chrome at {SIGNUP_URL} ...")
    subprocess.Popen([
        CHROME_PATH,
        f"--remote-debugging-port={DEBUG_PORT}",
        f"--user-data-dir={profile_dir}",
        "--no-first-run",
        "--no-default-browser-check",
        SIGNUP_URL,
    ])
    time.sleep(4)
    print("Chrome is open.")


if __name__ == "__main__":
    main()
