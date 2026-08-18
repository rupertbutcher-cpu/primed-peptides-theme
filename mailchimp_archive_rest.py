"""
Archives every Primed Peptides Mailchimp contact except the 100 selected
for the initial test group (best100_2026-08-18.csv, picked by data
completeness). Full backup already exists in Downloads.

Fix from the first attempt: archiving is DELETE on the member resource,
not PATCH {"status": "archived"} - that field value isn't valid via PATCH.
"""
import csv
import hashlib
import json
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

with open(r"C:\Users\Rupert\Downloads\primed_mailchimp_full_backup_2026-08-18.csv", encoding="utf-8") as f:
    all_rows = list(csv.DictReader(f))

with open(r"C:\Users\Rupert\Downloads\primed_mailchimp_best100_2026-08-18.csv", encoding="utf-8") as f:
    keep_emails = {row["email"].strip().lower() for row in csv.DictReader(f)}

to_archive = [r for r in all_rows if r["email"].strip().lower() not in keep_emails]
print(f"Keeping {len(keep_emails)} active, archiving {len(to_archive)}")

CHUNK = 500
batch_ids = []
for i in range(0, len(to_archive), CHUNK):
    chunk = to_archive[i:i + CHUNK]
    ops = []
    for r in chunk:
        h = hashlib.md5(r["email"].strip().lower().encode()).hexdigest()
        ops.append({
            "method": "DELETE",
            "path": f"/lists/{LIST_ID}/members/{h}",
        })
    resp = requests.post(f"{BASE}/batches", auth=AUTH, json={"operations": ops}, timeout=30)
    resp.raise_for_status()
    batch_id = resp.json()["id"]
    batch_ids.append(batch_id)
    print(f"  submitted batch {batch_id} ({len(chunk)} archive ops)")

print("Waiting for batches to complete...")
for batch_id in batch_ids:
    while True:
        resp = requests.get(f"{BASE}/batches/{batch_id}", auth=AUTH, timeout=20)
        status = resp.json()
        if status["status"] == "finished":
            print(f"  batch {batch_id}: finished, {status['finished_operations']}/{status['total_operations']} ops, {status['errored_operations']} errors")
            break
        time.sleep(3)

resp = requests.get(f"{BASE}/lists/{LIST_ID}", auth=AUTH, timeout=20)
stats = resp.json()["stats"]
print(f"\nFinal state: {stats['member_count']} subscribed")
