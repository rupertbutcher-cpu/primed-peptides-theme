# Primed Peptides

Repo: `rupertbutcher-cpu/primed-peptides-theme` · Path: `C:\Services\primed_peptides`
Site: primedpeptides.co.uk · Hosting: SiteGround · Theme folder: `public_html/wp-content/themes/primed-peptides-theme-1/`

## CRITICAL
- **NO Stripe or WooPayments** — will terminate account and freeze funds 90–180 days
- **Bank transfer only** (Revolut business) until a high-risk processor is approved
- **Keep completely separate from premiumpeptide.uk** — no cross-linking, no shared processors

## Payment processors in progress
- **Huch** — Arthur Soudais (arthur.soudais@huch.tech) · calendly.com/arthur-soudais-huch/30min — PRIORITY
- FirmEU (broker, Mahir Mian mahir@firmeu.com) — 3 providers introduced
- CorePay + PayFirmly — both awaiting response

## Audience
B2B / research. "Research Use Only" disclaimer in top bar and footer. Dark clinical design.

## Deploying theme changes
Upload files via SiteGround File Manager → `public_html/wp-content/themes/primed-peptides-theme-1/` → Purge SG Cache from WP Admin.

## API (SiteGround WAF)
WC REST API: query string auth + User-Agent `PrimedPeptides-Integration/1.0` + `-as [System.Net.HttpWebRequest]` in PowerShell.
WP REST API POST: still blocked — create pages manually via WP Admin.

## Referral system (?ref=rupert/rod/tom)
Cookie-based 30-day. Rod and Tom bank details still needed in functions.php.

## Google Business Profile
Review link: https://g.page/r/CX8JJyoCz0fwEBI/review

## Key pending
- Book call with Arthur at Huch (card payments)
- Add Rod + Tom bank details to functions.php
- Mailchimp for WooCommerce plugin + email signup + automations
- Trustpilot setup
- Testing & Quality page (WP Admin — API blocked)
- Low-stock alerts on all products
- Product bundles (Recovery Stack, Cognitive Stack, Performance Stack)
- New products: Rapid Recovery + Golden Glow (prices TBC), L-Glutathione (ask supplier)
