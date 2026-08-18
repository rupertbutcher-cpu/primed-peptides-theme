"""
Builds a branded Mailchimp email template for Primed Peptides - real logo,
real brand blue (#76b3f8, sampled from the actual logo file, not guessed),
dark navy text. Matches the visual language already used in the product
photos (ice-blue accents, dark navy labels).

Mailchimp requires *|UNSUB|* somewhere in the template for compliance -
without it, campaigns using this template can't be sent.
"""
import json
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

LOGO_URL = "https://primedpeptides.co.uk/wp-content/uploads/2026/07/PHOTO-2026-07-08-11-02-38.jpg"

HTML = f"""<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body {{ margin:0; padding:0; background:#f4f7fb; font-family: Arial, Helvetica, sans-serif; }}
  .wrapper {{ max-width:600px; margin:0 auto; background:#ffffff; }}
  .header {{ padding:32px 24px 24px; text-align:center; border-bottom:1px solid #eef2f7; }}
  .header img {{ height:100px; }}
  .content {{ padding:32px 28px; color:#141e37; font-size:15px; line-height:1.6; }}
  .content h1 {{ font-size:22px; color:#141e37; margin:0 0 16px; }}
  .content p {{ margin:0 0 16px; }}
  .cta {{ display:inline-block; background:#4a7fd4; color:#ffffff !important; text-decoration:none;
          padding:14px 28px; border-radius:4px; font-weight:bold; font-size:15px; margin:8px 0 24px; }}
  .disclaimer {{ font-size:12px; color:#8a93a6; border-top:1px solid #eef2f7; padding-top:16px; margin-top:24px; }}
  .footer {{ padding:24px 28px 32px; text-align:center; font-size:11px; color:#a3abb8; }}
  .footer a {{ color:#a3abb8; }}
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <img src="{LOGO_URL}" alt="Primed Peptides">
  </div>
  <div class="content" mc:edit="main_content">
    <h1>Your headline here</h1>
    <p>Your email content goes here.</p>
  </div>
  <div class="footer">
    *|LIST:ADDRESS|*<br>
    *|UNSUB|*
  </div>
</div>
</body>
</html>"""

resp = requests.post(f"{BASE}/templates", auth=AUTH, json={"name": "Primed Peptides - Branded", "html": HTML}, timeout=30)
if resp.status_code >= 300:
    print(resp.status_code, resp.text)
else:
    result = resp.json()
    print(f"Created template id {result['id']}: {result['name']}")
