import os
import shutil
from PIL import Image, ImageDraw

REPO_ROOT = r"c:\Web DPM\pemirafmipa.dpmfmipaunud.com\public_html"
PUBLIC_DIR = os.path.join(REPO_ROOT, "public")
IMAGES_DIR = os.path.join(PUBLIC_DIR, "images")

def optimize_starfield():
    starfield_png = os.path.join(PUBLIC_DIR, "StarfieldCanvas.png")
    starfield_webp = os.path.join(PUBLIC_DIR, "StarfieldCanvas.webp")
    if os.path.exists(starfield_png):
        with Image.open(starfield_png) as im:
            print(f"[Starfield] Original: {im.size}, {im.mode}, {os.path.getsize(starfield_png)} bytes")
            if im.mode in ("RGBA", "LA") or (im.mode == "P" and "transparency" in im.info):
                im_rgb = im.convert("RGB")
            else:
                im_rgb = im
            
            # Max width 1920
            if im_rgb.width > 1920:
                new_h = int(im_rgb.height * (1920 / im_rgb.width))
                im_rgb = im_rgb.resize((1920, new_h), Image.Resampling.LANCZOS)
            
            # Target 90-120 KB
            im_rgb.save(starfield_webp, "WEBP", quality=85, method=6)
            size_kb = os.path.getsize(starfield_webp) / 1024
            print(f"[Starfield] WebP size: {size_kb:.1f} KB")

def optimize_logo():
    logo_path = os.path.join(PUBLIC_DIR, "logo-pemira.webp")
    orig_png = os.path.join(PUBLIC_DIR, "Logo pemira.png")
    orig_webp = os.path.join(PUBLIC_DIR, "Logo pemira.webp")

    # If logo-pemira.webp is already optimized between 35 and 45 KB, keep it
    if os.path.exists(logo_path):
        current_kb = os.path.getsize(logo_path) / 1024
        if 35.0 <= current_kb <= 45.0:
            print(f"[Logo] Already optimized: {current_kb:.1f} KB")
            return

    source = orig_png if os.path.exists(orig_png) else (orig_webp if os.path.exists(orig_webp) else logo_path)
    
    with Image.open(source) as im:
        print(f"[Logo] Original from {os.path.basename(source)}: {im.size}, {im.mode}, {os.path.getsize(source)} bytes")
        # Resize to 512x512 with high quality Lanczos filter
        im_512 = im.resize((512, 512), Image.Resampling.LANCZOS)
        
        # Test qualities to hit ~35-45 KB target
        best_size = 0
        best_diff = 999999
        best_q = 60
        best_aq = 60
        for q in [75, 70, 65, 60, 55, 50]:
            for aq in [75, 70, 60, 50]:
                im_512.save(logo_path, "WEBP", quality=q, alpha_quality=aq, method=6)
                size_kb = os.path.getsize(logo_path) / 1024
                # Target is ~40 KB (between 35 and 45 KB)
                diff = abs(size_kb - 40.0)
                if 35.0 <= size_kb <= 45.0:
                    print(f"[Logo] HIT target: quality={q}, alpha_quality={aq} -> {size_kb:.1f} KB")
                    return
                if diff < best_diff:
                    best_diff = diff
                    best_size = size_kb
                    best_q = q
                    best_aq = aq
        
        # Fallback to closest
        im_512.save(logo_path, "WEBP", quality=best_q, alpha_quality=best_aq, method=6)
        print(f"[Logo] Closest: quality={best_q}, alpha_quality={best_aq} -> {best_size:.1f} KB")

def generate_result_frames():
    os.makedirs(IMAGES_DIR, exist_ok=True)
    
    # 1. Corner image - resize to max 256px width so it is ~20 KB
    corner_path = os.path.join(IMAGES_DIR, "corner-image.png")
    if os.path.exists(corner_path) and os.path.getsize(corner_path) > 1000:
        print(f"[Corner] Already exists: {os.path.getsize(corner_path)} bytes")
    else:
        im = Image.new("RGBA", (160, 160), (0, 0, 0, 0))
        draw = ImageDraw.Draw(im)
        gold = (201, 162, 39, 200)
        draw.line([(0, 20), (140, 20)], fill=gold, width=2)
        draw.line([(20, 0), (20, 140)], fill=gold, width=2)
        draw.line([(0, 40), (120, 40)], fill=gold, width=1)
        draw.line([(40, 0), (40, 120)], fill=gold, width=1)
        im.save(corner_path, "PNG", optimize=True)
        print(f"[Corner] Generated: {os.path.getsize(corner_path)} bytes")

    # 2. frame-bem.webp and frame-hima.webp
    for frame_name, border_color in [
        ("frame-bem.webp", (201, 162, 39, 230)), # gold for BEM
        ("frame-hima.webp", (84, 101, 209, 230))  # navy/indigo for HIMA
    ]:
        frame_file = os.path.join(IMAGES_DIR, frame_name)
        if os.path.exists(frame_file) and os.path.getsize(frame_file) > 500:
            print(f"[{frame_name}] Already exists: {os.path.getsize(frame_file)} bytes")
            continue
        w, h = 400, 520
        im = Image.new("RGBA", (w, h), (0, 0, 0, 0))
        draw = ImageDraw.Draw(im)
        
        # Subtle dark backdrop with transparency
        draw.rounded_rectangle([4, 4, w - 5, h - 5], radius=16, fill=(17, 22, 53, 140), outline=border_color, width=3)
        # Inner decorative line
        draw.rounded_rectangle([12, 12, w - 13, h - 13], radius=12, fill=None, outline=(border_color[0], border_color[1], border_color[2], 100), width=1)
        
        im.save(frame_file, "WEBP", quality=85)
        print(f"[{frame_name}] Generated: {os.path.getsize(frame_file)} bytes")

if __name__ == "__main__":
    optimize_starfield()
    optimize_logo()
    generate_result_frames()
