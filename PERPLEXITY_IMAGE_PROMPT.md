# Prompt to regenerate Primed Peptides cartridge photos

**Works with ChatGPT/DALL-E or any image-gen tool that can take a reference upload.** Rewritten 2026-08-11 — the original version referenced the *current* (wrong, vial-shaped) product photo and asked the AI to "replace the vial in the attached image," which likely explains why the first attempt didn't really work: an image reference tends to anchor the model on the input's shape, so a photo of a vial pulls it back toward vial-like results even when the text says otherwise. This version describes the cartridge shape in text only, and only asks for an image reference for brand style, not product shape.

**How to use:** for each product, run the prompt below with `[PRODUCT NAME]` and `[DOSAGE]` swapped for that product's real values (table below). Optionally attach the Primed Peptides logo (`assets/images/` or wherever the current logo file lives) **for colour palette and label typography only** — tell the tool explicitly not to take any shape/object cues from it, since it's a 2D logo, not a product photo.

---

## The prompt

```
Generate a photorealistic studio product photo of a 3ml research peptide pen cartridge. Do not reference or reproduce any vial, ampoule, or bottle shape — this is a cartridge, not a vial.

Shape (must match exactly — identical in form to a standard reusable insulin-pen-style injector cartridge):
- Slim cylindrical glass barrel, 12mm diameter x 45mm length
- Clear glass body showing a small amount of clear/pale liquid inside
- Rubber plunger visible at the base
- Crimped silver aluminium cap at the top with a rubber septum for pen-needle piercing
- NOT a rounded vial, NOT a wide-mouth bottle, NOT a dropper bottle

Label (wrapped around the middle third of the cartridge body):
- Product name: [PRODUCT NAME]
- Dosage: [DOSAGE]
- Small print: "Research Use Only — Not for Human Consumption"
- Clean, minimal, professional pharmaceutical label — sans-serif type, dark/clinical colour scheme matching the attached Primed Peptides logo (use the logo only for colour palette and typography style, not for any shape or object reference), small batch/lot number placeholder

Photography style:
- Soft ice-blue gradient studio background
- Single cartridge standing upright, centred, subtle drop shadow
- Sharp focus, high resolution, professional e-commerce product photography, square 1:1 crop
- No watermarks, no text anywhere outside the label itself
```

---

## Per-product substitution table

Dosages corrected 2026-08-11 to match the live site (3 were wrong before: MOT-C, DISP, NAD+ — same class of labelling error as the Ipamorelin/CJC1295 fix from 2026-08-09).

| [PRODUCT NAME] | [DOSAGE] | Live now? |
|---|---|---|
| BPC157 + TB500 Cartridge | BPC-157 10mg + TB-500 10mg | Live |
| Ipamorelin + CJC1295 Cartridge | 5mg + 5mg (10mg total) | Live |
| Tesamorelin Cartridge | 20mg | Live |
| MOT-C Cartridge | 30mg | Live |
| GHK-Cu Cartridge | 100mg | Live |
| DISP Cartridge | 10mg | **Draft — pulled 2026-08-11, ChemResearch out of stock** |
| NAD+ Cartridge | 500mg | Live |
| Semax Cartridge | 20mg | Live |
| Kisspeptin Cartridge | 5mg | **Draft — pulled 2026-08-11, ChemResearch doesn't make a cartridge version** |
| SS31 Cartridge | 10mg | Draft |
| Selank Cartridge | 20mg | Draft |
| Wolverine Stack Cartridge | 20mg | Draft — likely the same product as BPC157+TB500, worth confirming with Danny before making a separate image |
| Recovery Pen | 30mg | Draft — unclear which real product this maps to, worth confirming with Danny first |

**Priority order**: do the 6 "Live" ones first — those are what customers actually see. The draft ones can wait, especially DISP/Kisspeptin/Wolverine/Recovery Pen given the open questions above.

## After generating
Once you've got a new image back:
1. Send it to me and I'll upload it straight to the matching WooCommerce product via the API, or
2. Upload it yourself via WP Admin → Products → [product] → Product image, then Purge SG Cache from the top bar.
