"""
One-off: exports the full ~9,032-contact "Primed Peptides" Mailchimp
audience to a local CSV (NOT committed to git - this repo is public as of
2026-08-18), then archives all but a random 100-contact test group so the
account fits under Mailchimp's current free-plan cap (250 contacts) before
any sending is attempted.

Run once. Full backup goes to Rupert's Downloads folder, well outside any
git-tracked directory.
"""
import csv
import json
import random
import time
import requests

ENV_PATH = r"C:\Services\primed_peptides\.env"
env = {}
with open(ENV_PATH) as f:
    for line in f:
        line = line.strip()
        if line and "=" in line:
            k, v = line.split("=", 1)
            env[k] = v

KEY = env["MAILCHIMP_API_KEY"]
DC = KEY.rsplit("-", 1)[-1]
BASE = f"https://{DC}.api.mailchimp.com/3.0"
AUTH = ("anystring", KEY)
LIST_ID = "8848d51eea"

BACKUP_CSV = r"C:\Users\Rupert\Downloads\primed_mailchimp_full_backup_2026-08-18.csv"
TEST_GROUP_SIZE = 100
random.seed(42)  # reproducible selection


def fetch_all_members():
    members = []
    offset = 0
    count = 1000
    while True:
        resp = requests.get(
            f"{BASE}/lists/{LIST_ID}/members",
            auth=AUTH,
            params={"count": count, "offset": offset, "status": "subscribed"},
            timeout=30,
        )
        resp.raise_for_status()
        batch = resp.json()["members"]
        if not batch:
            break
        members.extend(batch)
        offset += count
        print(f"  fetched {len(members)} so far...")
        if len(batch) < count:
            break
    return members


def main():
    print("Fetching full member list...")
    members = fetch_all_members()
    print(f"Total fetched: {len(members)}")

    print(f"Writing full backup to {BACKUP_CSV}")
    with open(BACKUP_CSV, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["email", "status", "fname", "lname", "phone", "city", "county", "postcode", "country", "tags", "timestamp_opt", "source"])
        for m in members:
            mf = m.get("merge_fields", {})
            writer.writerow([
                m["email_address"], m["status"], mf.get("FNAME", ""), mf.get("LNAME", ""),
                mf.get("PHONE", ""), mf.get("MMERGE7", ""), mf.get("MMERGE8", ""), mf.get("MMERGE9", ""), mf.get("MMERGE10", ""),
                ";".join(t["name"] for t in m.get("tags", [])), m.get("timestamp_opt", ""), m.get("source", ""),
            ])
    print(f"Backup written: {len(members)} contacts")

    # pick test group
    test_group = set(random.sample([m["email_address"] for m in members], TEST_GROUP_SIZE))
    to_archive = [m for m in members if m["email_address"] not in test_group]
    print(f"Keeping {len(test_group)} active, archiving {len(to_archive)}")

    with open(r"C:\Users\Rupert\Downloads\primed_mailchimp_test_group_2026-08-18.csv", "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["email", "fname", "lname"])
        for m in members:
            if m["email_address"] in test_group:
                mf = m.get("merge_fields", {})
                writer.writerow([m["email_address"], mf.get("FNAME", ""), mf.get("LNAME", "")])
    print("Test group list saved separately too.")

    # Archive the rest in batches of 500 via the Mailchimp batch API.
    # Archiving is DELETE on the member resource (soft-delete, data retained,
    # status becomes "archived") - NOT PATCH {"status": "archived"}, which
    # Mailchimp rejects with "not a valid choice" since that's not a
    # PATCH-settable value. Found the hard way 2026-08-18 - first attempt
    # with PATCH errored on all 8,932 operations with zero actual effect.
    CHUNK = 500
    batch_ids = []
    for i in range(0, len(to_archive), CHUNK):
        chunk = to_archive[i:i + CHUNK]
        ops = []
        for m in chunk:
            ops.append({
                "method": "DELETE",
                "path": f"/lists/{LIST_ID}/members/{m['id']}",
            })
        resp = requests.post(f"{BASE}/batches", auth=AUTH, json={"operations": ops}, timeout=30)
        resp.raise_for_status()
        batch_id = resp.json()["id"]
        batch_ids.append(batch_id)
        print(f"  submitted batch {batch_id} ({len(chunk)} archive ops)")

    # poll until all batches finish
    print("Waiting for batches to complete...")
    for batch_id in batch_ids:
        while True:
            resp = requests.get(f"{BASE}/batches/{batch_id}", auth=AUTH, timeout=20)
            status = resp.json()
            if status["status"] == "finished":
                print(f"  batch {batch_id}: finished, {status['finished_operations']}/{status['total_operations']} ops, {status['errored_operations']} errors")
                break
            time.sleep(3)

    # verify final count
    resp = requests.get(f"{BASE}/lists/{LIST_ID}", auth=AUTH, timeout=20)
    stats = resp.json()["stats"]
    print(f"\nFinal state: {stats['member_count']} subscribed, {stats.get('non_subscriber_count', '?')} non-subscribers (archived etc.)")


if __name__ == "__main__":
    main()
