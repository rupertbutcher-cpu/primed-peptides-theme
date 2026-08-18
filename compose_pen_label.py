"""
Composites the real Primed Peptides logo + a precise, code-rendered dosage
label onto the AI-generated blank master pen photo (generated-images/master_pen_blank.png).

Why: gpt-image-1 (and every AI image model tried) renders small label text
unreliably - real failures seen 2026-08-18 included "10 ng + 10 mg" instead
of "10mg + 10mg" (wrong unit, not just ugly) and a garbled "PEPTIDES"
sub-logo. Rendering text with PIL instead guarantees it's pixel-correct,
and lets one AI-generated blank pen photo be reused for every product
instead of paying for + risking a new generation per product.

Usage: python compose_pen_label.py "<Product Name>" "<Dosage line>" <output_filename>
Example: python compose_pen_label.py "BPC157 + TB500" "10mg + 10mg" bpc157_tb500.png
"""
import sys
import math
import qrcode
import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageOps

MASTER = r"C:\Services\primed_peptides\generated-images\master_pen_blank.png"
LOGO = r"C:\Users\Rupert\AppData\Local\Temp\claude\c--Services\5fc02cb3-e561-429d-8d08-b81b91ab9ded\scratchpad\logo_original.jpg"
OUT_DIR = r"C:\Services\primed_peptides\generated-images"

FONT_BOLD = r"C:\Windows\Fonts\arialbd.ttf"
FONT_REG = r"C:\Windows\Fonts\arial.ttf"

# COA verification page - product-level, not batch-specific, since this same
# photo is reused across every batch. Page doesn't exist live yet as of
# 2026-08-18 - needs building in WP Admin (COA Batch template already exists
# in the theme) before this QR resolves to anything.
COA_URL = "https://primedpeptides.co.uk/coa/"

# Barrel region estimates (1024x1024 master image) - visually measured
LOGO_BOX = (455, 160, 570, 430)     # x0,y0,x1,y1 - upper barrel segment
LABEL_BOX = (450, 500, 575, 700)    # lower barrel segment, below silver band
# Small, deliberately not scannable-from-photo - the real QR goes on the
# physical product's own printed label. This is for visual realism only,
# so it's curved to look wrapped around the barrel instead of pasted flat.
QR_BOX = (462, 630, 562, 695)


def cylindrical_wrap(img, extent=0.92, shade_strength=0.21):
    """Horizontally warps a flat image to look wrapped around a cylinder
    viewed face-on: content compresses toward the left/right edges (as it
    curves away from the viewer) and darkens slightly there, same as a real
    label wrapped around a pen barrel would look."""
    arr = np.array(img.convert("RGBA"), dtype=np.float64)
    h, w = arr.shape[0], arr.shape[1]
    out = np.zeros_like(arr)

    xs_screen = np.linspace(-extent, extent, w)
    theta_max = math.asin(extent)
    src_u = np.array([math.asin(x) / theta_max for x in xs_screen])  # -1..1
    src_cols = ((src_u + 1) / 2 * (w - 1)).round().astype(int)
    src_cols = np.clip(src_cols, 0, w - 1)

    out = arr[:, src_cols, :]

    # shading: darken toward the edges to imply the surface curving away
    shade = 1 - shade_strength * (np.abs(xs_screen) / extent) ** 2
    out[:, :, 0] *= shade
    out[:, :, 1] *= shade
    out[:, :, 2] *= shade

    return Image.fromarray(np.clip(out, 0, 255).astype(np.uint8), "RGBA")


def make_transparent(img, threshold=235):
    """Turn near-white pixels transparent so the logo blends onto the pen's white barrel."""
    img = img.convert("RGBA")
    data = img.getdata()
    new_data = []
    for r, g, b, a in data:
        if r > threshold and g > threshold and b > threshold:
            new_data.append((r, g, b, 0))
        else:
            new_data.append((r, g, b, 255))
    img.putdata(new_data)
    return img


def main():
    product_name, dosage_line, out_filename = sys.argv[1], sys.argv[2], sys.argv[3]

    pen = Image.open(MASTER).convert("RGBA")

    # --- Logo: crop tight, make white transparent, rotate to run up the barrel ---
    logo = Image.open(LOGO).convert("RGB")
    # crop tight around the wordmark (the source is a wide image with lots of white margin)
    bbox = ImageOps.invert(logo.convert("L")).getbbox()
    logo = logo.crop(bbox)
    logo = make_transparent(logo)

    logo_w, logo_h = LOGO_BOX[2] - LOGO_BOX[0], LOGO_BOX[3] - LOGO_BOX[1]
    # rotate 90 so it reads bottom-to-top running up the barrel, matching the reference photo
    logo_rot = logo.rotate(90, expand=True)
    scale = min(logo_w / logo_rot.width, logo_h / logo_rot.height)
    logo_rot = logo_rot.resize((int(logo_rot.width * scale), int(logo_rot.height * scale)), Image.LANCZOS)
    lx = LOGO_BOX[0] + (logo_w - logo_rot.width) // 2
    ly = LOGO_BOX[1] + (logo_h - logo_rot.height) // 2
    pen.alpha_composite(logo_rot, (lx, ly))

    # --- Label: precise text, drawn directly (guaranteed correct) ---
    draw = ImageDraw.Draw(pen)
    box_x0, box_y0, box_x1, box_y1 = LABEL_BOX
    box_w = box_x1 - box_x0
    cx = (box_x0 + box_x1) // 2

    name_font = ImageFont.truetype(FONT_BOLD, 15)
    dose_font = ImageFont.truetype(FONT_BOLD, 14)
    fine_font = ImageFont.truetype(FONT_REG, 9)

    def wrap_to_width(text, font, max_w):
        words = text.split()
        lines, cur = [], ""
        for w in words:
            trial = (cur + " " + w).strip()
            if draw.textlength(trial, font=font) <= max_w:
                cur = trial
            else:
                if cur:
                    lines.append(cur)
                cur = w
        if cur:
            lines.append(cur)
        return lines

    pad = 8
    name_lines = wrap_to_width(product_name, name_font, box_w - pad * 2)
    dose_lines = wrap_to_width(dosage_line, dose_font, box_w - pad * 2)
    fine_lines = wrap_to_width("Research Use Only - Not for Human Consumption", fine_font, box_w - pad * 2)

    y = box_y0 + 14
    for line in name_lines:
        w = draw.textlength(line, font=name_font)
        draw.text((cx - w / 2, y), line, font=name_font, fill=(20, 30, 55, 255))
        y += 19
    y += 6
    for line in dose_lines:
        w = draw.textlength(line, font=dose_font)
        draw.text((cx - w / 2, y), line, font=dose_font, fill=(30, 60, 110, 255))
        y += 18
    y += 10
    for line in fine_lines:
        w = draw.textlength(line, font=fine_font)
        draw.text((cx - w / 2, y), line, font=fine_font, fill=(90, 95, 100, 255))
        y += 12

    # --- QR code: links to the product-level COA verification page ---
    # Not meant to be scannable from this photo (the real QR goes on the
    # physical product's own label) - curved for visual realism instead.
    qr = qrcode.QRCode(border=1, box_size=10)
    qr.add_data(COA_URL)
    qr.make(fit=True)
    qr_img = qr.make_image(fill_color=(20, 30, 55), back_color="white").convert("RGBA")
    qr_w = qr_h = min(QR_BOX[2] - QR_BOX[0], QR_BOX[3] - QR_BOX[1])
    qr_img = qr_img.resize((qr_w, qr_h), Image.LANCZOS)
    qr_img = cylindrical_wrap(qr_img)
    qx = QR_BOX[0] + ((QR_BOX[2] - QR_BOX[0]) - qr_w) // 2
    qy = QR_BOX[1]
    pen.alpha_composite(qr_img, (qx, qy))
    tiny_font = ImageFont.truetype(FONT_REG, 7)
    caption = "Scan to verify"
    cw = draw.textlength(caption, font=tiny_font)
    draw.text((cx - cw / 2, qy + qr_h + 3), caption, font=tiny_font, fill=(110, 115, 120, 255))

    out_path = f"{OUT_DIR}\\{out_filename}"
    pen.convert("RGB").save(out_path, "PNG")
    print("saved", out_path)


if __name__ == "__main__":
    main()
