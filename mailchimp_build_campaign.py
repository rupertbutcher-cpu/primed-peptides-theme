"""
Builds the Primed Peptides NAD+ educational campaign as a Mailchimp draft
(not sent) using the branded template, ready for Rupert to preview in
Mailchimp's own interface.
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
LIST_ID = "8848d51eea"
TEMPLATE_ID = 13493131

campaign_payload = {
    "type": "regular",
    "recipients": {"list_id": LIST_ID},
    "settings": {
        "subject_line": "What are research peptides? A quick primer",
        "preview_text": "The science behind the compounds you're researching.",
        "title": "NAD+ educational campaign - 2026-08-18 draft",
        "from_name": "Primed Peptides",
        "reply_to": "info@primedpeptides.co.uk",
        "template_id": TEMPLATE_ID,
    },
}

resp = requests.post(f"{BASE}/campaigns", auth=AUTH, json=campaign_payload, timeout=30)
resp.raise_for_status()
campaign = resp.json()
campaign_id = campaign["id"]
print(f"Created campaign {campaign_id}")

content_html = """
<h1>What are research peptides?</h1>
<p>Peptides are short chains of amino acids - the same building blocks as
proteins, just smaller. Because of their size and structure, research
peptides are studied for highly specific roles in cellular signalling:
tissue repair, metabolic regulation, and mitochondrial function among
them.</p>
<p>What makes a research peptide useful in a lab setting isn't just the
compound itself - it's purity and consistency. That's why every Primed
Peptides cartridge is manufactured to a stated specification.</p>
<h1 style="margin-top:28px;">This week, we're spotlighting NAD+.</h1>
<p>NAD+ (Nicotinamide Adenine Dinucleotide) is a coenzyme central to
cellular energy metabolism - present in every living cell, and a
frequent subject in mitochondrial function and longevity research. Our
500mg stabilised cartridge is manufactured to the same standard as the
rest of the range.</p>
<p><a class="cta" href="https://primedpeptides.co.uk/product/nad-cartridge-1000mg/">View NAD+ Cartridge 500mg</a></p>
<p class="disclaimer"><strong>Research Use Only. Not for human consumption.</strong></p>
<p>&mdash; The Primed Peptides team</p>
"""

resp2 = requests.put(
    f"{BASE}/campaigns/{campaign_id}/content",
    auth=AUTH,
    json={"template": {"id": TEMPLATE_ID, "sections": {"main_content": content_html}}},
    timeout=30,
)
if resp2.status_code >= 300:
    print("Content update failed:", resp2.status_code, resp2.text)
else:
    print("Content set successfully.")
    print(f"\nPreview/edit in Mailchimp: https://{DC}.admin.mailchimp.com/campaigns/edit?id={campaign['web_id']}")
