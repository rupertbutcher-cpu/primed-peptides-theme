"""
Logo-only variant of compose_pen_label.py, for the Reusable Metal Pen
listing itself - no dosage label or QR code needed since it's not a
specific peptide product, just the pen.
"""
from PIL import Image, ImageOps

MASTER = r"C:\Services\primed_peptides\generated-images\master_pen_blank.png"
LOGO = r"C:\Users\Rupert\AppData\Local\Temp\claude\c--Services\5fc02cb3-e561-429d-8d08-b81b91ab9ded\scratchpad\logo_original.jpg"
OUT_PATH = r"C:\Services\primed_peptides\generated-images\reusable_metal_pen.png"

LOGO_BOX = (455, 160, 570, 430)


def make_transparent(img, threshold=235):
    img = img.convert("RGBA")
    data = list(img.getdata())
    new_data = [
        (r, g, b, 0) if (r > threshold and g > threshold and b > threshold) else (r, g, b, 255)
        for r, g, b, a in data
    ]
    img.putdata(new_data)
    return img


def main():
    pen = Image.open(MASTER).convert("RGBA")

    logo = Image.open(LOGO).convert("RGB")
    bbox = ImageOps.invert(logo.convert("L")).getbbox()
    logo = logo.crop(bbox)
    logo = make_transparent(logo)

    logo_w, logo_h = LOGO_BOX[2] - LOGO_BOX[0], LOGO_BOX[3] - LOGO_BOX[1]
    logo_rot = logo.rotate(90, expand=True)
    scale = min(logo_w / logo_rot.width, logo_h / logo_rot.height)
    logo_rot = logo_rot.resize((int(logo_rot.width * scale), int(logo_rot.height * scale)), Image.LANCZOS)
    lx = LOGO_BOX[0] + (logo_w - logo_rot.width) // 2
    ly = LOGO_BOX[1] + (logo_h - logo_rot.height) // 2
    pen.alpha_composite(logo_rot, (lx, ly))

    pen.convert("RGB").save(OUT_PATH, "PNG")
    print("saved", OUT_PATH)


if __name__ == "__main__":
    main()
