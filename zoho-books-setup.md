# Zoho Books — setup pack

Prepared 2026-08-30. Everything here is ready to enter; the account itself has
to be created by you (it needs email verification against a real inbox).

---

## 1. Correction to the earlier plan — read this first

The todo said Zoho Books free "connects natively to the existing Revolut
Business account (auto-syncs transactions)". **That is wrong.**

Checked against Zoho's own UK pricing page:

| | Free | Standard |
|---|---|---|
| Price | £0 | £10/mo billed annually (£12 monthly) |
| Invoices / expenses per year | 1,000 each | more |
| Users | 1 + 1 accountant | 3 |
| VAT return tracking + MTD filing | ✅ | ✅ |
| P&L, balance sheet, 50+ reports | ✅ | ✅ |
| **Bank feeds (auto-sync)** | ❌ **not included** | ✅ |

So on Free you import bank transactions by CSV from Revolut rather than having
them flow in automatically. At current volumes that is perhaps ten minutes a
month, and £120/yr saved. Worth starting Free and upgrading if the manual
import becomes annoying.

**Also worth checking when you sign up:** Zoho has historically applied a
turnover cap to its free tier in some regions. The current UK pricing page does
not state one, but confirm during signup rather than discovering it later.

---

## 2. The entity

| | |
|---|---|
| Company | **Premium Wellness Ltd** |
| Incorporated | 29 August 2026 |
| SIC | 47910 (retail sale via mail order/internet) |
| Registered office | 71-75 Shelton Street, Covent Garden, London WC2H 9JQ |
| Director / 100% shareholder | Katsiaryna Valetava |
| Bank | Revolut Business (existing) |
| Trading as | Primed Peptides |

**Open question:** Premium Peptide is still listed on the todo as needing its
own Ltd company, separate from Primed. If that happens, it needs **separate
books** — a second Zoho organisation, not a second brand inside this one. Zoho
Free allows one organisation, so a second company means either a second free
account or a paid plan.

Until that is settled, set Zoho up for **Premium Wellness Ltd only**.

---

## 3. Chart of accounts

Zoho creates a generic default set. Add these so the numbers mean something
for this business specifically.

### Income
- Product Sales — Primed Peptides
- Product Sales — Premium Peptide *(only if same entity)*
- Shipping Income
- Refunds & Returns *(contra-income, not an expense)*

### Cost of sales
- Product Purchases — ChemResearch
- Packaging & Labels *(cartridge labels, boxes, mailers)*
- Postage & Fulfilment *(Royal Mail)*
- Payment Processing Fees *(PayPal on Primed, Stripe on Premium — keep separate)*

Gross margin is meaningless if processing fees sit in overheads, so keep them
in cost of sales.

### Operating expenses
- Website & Hosting *(SiteGround)*
- Software & Subscriptions *(Zoho, Mailchimp, CircleLoop)*
- Telephone *(CircleLoop — 020 8064 3073 and 020 8064 2459)*
- Marketing & Advertising *(Instagram ads when they start)*
- Professional Fees *(1st Formations, accountant)*
- Trade Memberships *(HFMA if accepted)*
- Regulatory & Compliance *(ICO registration ~£40/yr, lab testing)*
- Laboratory Testing *(Janoshik, when batches are sent)*
- Insurance
- Bank Charges

### Assets
- Stock / Inventory
- Revolut Business — current account

---

## 4. Setup order

1. **Create the organisation** — Premium Wellness Ltd, UK, GBP, financial year
   end matching Companies House (31 August 2027 for a 29 Aug 2026
   incorporation, unless changed).
2. **VAT: leave registration off for now.** The UK threshold is £90,000
   rolling 12-month turnover. Set VAT to "not registered" and revisit as
   revenue grows — registering voluntarily too early adds 20% to consumer
   prices with no benefit on a direct-to-consumer product.
3. **Add the chart of accounts above.**
4. **Add Revolut Business as a bank account** (manual, not a feed, on Free).
5. **Import opening transactions** — export from Revolut as CSV, import in
   Zoho under Banking. Do this monthly.
6. **Add ChemResearch as a supplier** with the real cost prices, so cost of
   sales comes through automatically rather than being estimated.
7. **Invite the accountant** as the free second user when you have one.

---

## 5. Two things that are missing and probably shouldn't be

**Product liability insurance.** Not on the todo anywhere. Selling ingestible
supplements direct to consumers without it is a real exposure, and most
retailers and trade bodies expect it. Worth pricing before launch.

**Corporation Tax registration.** Already on the todo — due within 3 months of
starting to trade. The company was incorporated 29 August, so if trading starts
at go-live next week the deadline is roughly early December 2026. Self-serve
via HMRC.

---

## 6. What I can do once the account exists

Zoho Books has a full REST API. If you create the organisation and generate an
API token, I can:

- create the entire chart of accounts programmatically rather than by hand
- push the ChemResearch cost list in as supplier items
- pull WooCommerce orders from both sites and reconcile against Revolut
- generate monthly P&L by brand

Say the word once it exists and I will wire that up.
