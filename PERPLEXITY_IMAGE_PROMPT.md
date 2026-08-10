# Prompt for Perplexity — redo Primed Peptides cartridge photos

**How to use:** for each product in `current-product-images/INDEX.md`, upload that product's current image to Perplexity alongside the prompt below, with `[PRODUCT NAME]` and `[DOSAGE]` swapped for that product's real values (see the table). Don't run this on the 2 accessory images (pen/case) — those aren't cartridges.

---

## The prompt

```
Generate a photorealistic studio product photo of a 3ml research peptide pen cartridge, replacing the vial shown in the attached reference image.

Cartridge specs (must match exactly):
- Standard reusable injector pen cartridge format (the same shape as an insulin pen cartridge), NOT a vial or ampoule
- 3ml capacity, cylindrical glass barrel, 12mm diameter x 45mm length
- Clear glass body showing a small amount of clear/pale liquid inside
- Rubber plunger visible at the base
- Crimped silver aluminium cap at the top with a rubber septum for pen-needle piercing

Label (wrapped around the middle third of the cartridge body):
- Product name: [PRODUCT NAME]
- Dosage: [DOSAGE]
- Small print: "Research Use Only — Not for Human Consumption"
- Clean, minimal, professional pharmaceutical label — sans-serif type, dark/clinical colour scheme (matches a UK research-peptide brand called Primed Peptides), small batch/lot number placeholder

Photography style:
- Soft ice-blue gradient studio background
- Single cartridge standing upright, centred, subtle drop shadow
- Sharp focus, high resolution, professional e-commerce product photography
- No watermarks, no text anywhere outside the label itself
- Keep the same framing/crop style as the reference image so it drops into the existing product grid cleanly
```

---

## Per-product substitution table

| [PRODUCT NAME] | [DOSAGE] |
|---|---|
| BPC157 + TB500 Cartridge | BPC-157 10mg + TB-500 10mg |
| Ipamorelin + CJC1295 Cartridge | 5mg + 5mg (10mg total) |
| Tesamorelin Cartridge | 20mg |
| MOTS-C Cartridge | 10mg |
| GHK-Cu Cartridge | 100mg |
| DISP Cartridge | 5mg |
| NAD+ Cartridge | 1000mg |
| Semax Cartridge | 20mg |
| Kisspeptin Cartridge | 5mg |
| SS31 Cartridge | 10mg |
| Selank Cartridge | 20mg |
| Wolverine Stack Cartridge | 20mg |
| Recovery Pen | 30mg |

Note: the current live label for MOT-C is missing the "S" (should be MOTS-C) — worth fixing in the new image and in the WooCommerce product title at the same time.

## After generating
Once you've got a new image back from Perplexity, either:
1. Send it to me and I'll upload it straight to the matching WooCommerce product via the API, or
2. Upload it yourself via WP Admin → Products → [product] → Product image, then Purge SG Cache from the top bar.
