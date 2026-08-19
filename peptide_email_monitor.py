"""
Checks both peptide-brand inboxes (Primed + Premium) for new mail and WhatsApps
Rupert a summary of anything new. Covers both brands despite living in this repo -
same "capability lives wherever it was built first" situation flagged 2026-08-19,
see capabilities.md.

Run on demand, or install as a scheduled task (see install-peptide-email-monitor-task.ps1)
to actually run unattended - not yet installed, needs an admin PowerShell on the server.

Run once: python peptide_email_monitor.py
"""
import imaplib
import email
from email.header import decode_header
import json
import os
import urllib.request

STATE_FILE = os.path.join(os.path.dirname(__file__), "peptide_email_monitor_state.json")

MAILBOXES = [
    {
        "name": "Primed Peptides",
        "env_path": r"C:\Services\primed_peptides\.env",
        "imap_host": "mail.primedpeptides.co.uk",
    },
    {
        "name": "Premium Peptide",
        "env_path": r"C:\Services\premium_peptide\.env",
        "imap_host": "gnldm1052.siteground.biz",
    },
]

BOT_SEND_URL = "http://localhost:3001/api/internal/send"
BOT_SECRET = "hs-int-2026"
ALERT_TO = "447545451386"  # Rupert


def load_env(path):
    env = {}
    with open(path) as f:
        for line in f:
            line = line.strip()
            if line and "=" in line and not line.startswith("#"):
                k, v = line.split("=", 1)
                env[k] = v
    return env


def load_state():
    if os.path.exists(STATE_FILE):
        with open(STATE_FILE) as f:
            return json.load(f)
    return {}


def save_state(state):
    with open(STATE_FILE, "w") as f:
        json.dump(state, f, indent=2)


def decode_str(s):
    if not s:
        return ""
    parts = decode_header(s)
    out = ""
    for text, enc in parts:
        if isinstance(text, bytes):
            out += text.decode(enc or "utf-8", errors="replace")
        else:
            out += text
    return out


def send_whatsapp(text):
    body = json.dumps({"secret": BOT_SECRET, "waId": ALERT_TO + "@c.us", "text": text}).encode()
    req = urllib.request.Request(BOT_SEND_URL, data=body, headers={"Content-Type": "application/json"}, method="POST")
    urllib.request.urlopen(req, timeout=15)


def check_mailbox(mb, state):
    env = load_env(mb["env_path"])
    user, pw = env["EMAIL_USER"], env["EMAIL_PASS"]

    m = imaplib.IMAP4_SSL(mb["imap_host"], 993, timeout=15)
    m.login(user, pw)
    m.select("INBOX", readonly=True)

    status, data = m.uid("search", None, "ALL")
    all_uids = [int(u) for u in data[0].split()] if data[0] else []
    # Per-mailbox baseline, not a single global "first run" flag - a mailbox that failed
    # (or was added later) gets its own baseline-only pass rather than either re-alerting
    # on everything or being silently skipped.
    is_baseline_run = mb["name"] not in state
    last_seen = state.get(mb["name"], {}).get("last_uid", 0)

    if is_baseline_run:
        new_uids = []
    else:
        new_uids = [u for u in all_uids if u > last_seen]

    new_mail = []
    for uid in new_uids:
        status, msg_data = m.uid("fetch", str(uid), "(BODY.PEEK[HEADER.FIELDS (FROM SUBJECT)])")
        if not msg_data or not msg_data[0]:
            continue
        raw = msg_data[0][1]
        msg = email.message_from_bytes(raw)
        new_mail.append({
            "from": decode_str(msg.get("From")),
            "subject": decode_str(msg.get("Subject")),
        })

    m.logout()

    highest = max(all_uids) if all_uids else last_seen
    state.setdefault(mb["name"], {})["last_uid"] = highest
    return new_mail


def main():
    state = load_state()

    all_new = {}
    baselined = []
    for mb in MAILBOXES:
        was_baseline = mb["name"] not in state
        try:
            new_mail = check_mailbox(mb, state)
            if was_baseline:
                baselined.append(mb["name"])
            elif new_mail:
                all_new[mb["name"]] = new_mail
        except Exception as e:
            print(f"[{mb['name']}] ERROR: {e}")

    save_state(state)

    if baselined:
        print("Baseline recorded (no alert) for:", ", ".join(baselined))

    if not all_new:
        print("No new mail.")
        return

    lines = ["New email:"]
    for name, mails in all_new.items():
        for mail in mails:
            lines.append(f"\n[{name}]\nFrom: {mail['from']}\nSubject: {mail['subject']}")
    text = "\n".join(lines)
    print(text)
    send_whatsapp(text)


if __name__ == "__main__":
    main()
