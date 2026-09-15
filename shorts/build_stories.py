#!/usr/bin/env python3
"""Story slides styled for маринаполтавцева.рф brand system."""

from __future__ import annotations

import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

W, H = 1080, 1920
OUT = Path("/workspace/shorts/stories")
ART = Path("/opt/cursor/artifacts/stories")
ASSETS = Path("/opt/cursor/artifacts/assets")

# Brand tokens from site CSS
PRIMARY = (222, 107, 125)  # #de6b7d
PRIMARY_EMPH = (145, 55, 71)
INK = (43, 33, 35)  # #2b2123
BG = (246, 243, 242)  # #f6f3f2
BG_SOFT = (253, 252, 252)  # #fdfcfc
WHITE = (255, 255, 255)
MUTED = (43, 33, 35, 158)  # ~0.62
SECONDARY = (43, 33, 35, 194)  # ~0.76

FONT_REG = "/usr/share/fonts/truetype/macos/Inter-Regular.ttf"
FONT_MED = "/usr/share/fonts/truetype/macos/Inter-Medium.ttf"
FONT_SEMI = "/usr/share/fonts/truetype/macos/Inter-SemiBold.ttf"
FONT_BOLD = "/usr/share/fonts/truetype/macos/Inter-Bold.ttf"


def F(path: str, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(path, size)


def make_bg() -> Image.Image:
    """Light brand background: soft pink radials like the site body."""
    img = Image.new("RGB", (W, H), BG)
    # optional soft photo texture blended lightly
    tex_path = ASSETS / "marina-story-bg.png"
    if tex_path.exists():
        tex = Image.open(tex_path).convert("RGB").resize((W, H), Image.Resampling.LANCZOS)
        img = Image.blend(img, tex, 0.28)

    overlay = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(overlay)

    # radial-ish soft blobs via ellipses + blur
    for cx, cy, r, a in [
        (-80, -100, 980, 28),
        (W + 40, 40, 820, 18),
        (W // 2, H - 80, 900, 12),
    ]:
        blob = Image.new("RGBA", (W, H), (0, 0, 0, 0))
        bd = ImageDraw.Draw(blob)
        bd.ellipse((cx - r, cy - r, cx + r, cy + r), fill=(*PRIMARY, a))
        blob = blob.filter(ImageFilter.GaussianBlur(90))
        overlay = Image.alpha_composite(overlay, blob)

    return Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGBA")


def text_wh(draw, text, font):
    b = draw.textbbox((0, 0), text, font=font)
    return b[2] - b[0], b[3] - b[1]


def draw_text(draw, text, xy, font, fill, anchor=None):
    draw.text(xy, text, font=font, fill=fill, anchor=anchor)


def centered_lines(draw, lines, y, font, fill, gap=1.18):
    cy = y
    for line in lines:
        tw, th = text_wh(draw, line, font)
        draw_text(draw, line, ((W - tw) // 2, cy), font, fill)
        cy += int(th * gap)
    return cy


def brand_header(draw, img):
    """Logo-style brand mark — site uses medium Inter headline."""
    label = "Марина Полтавцева"
    font = F(FONT_MED, 30)
    tw, th = text_wh(draw, label, font)
    x = (W - tw) // 2
    y = 150
    draw_text(draw, label, (x, y), font, (*INK, 230))
    # soft underline accent
    line_w = min(tw, 220)
    lx = (W - line_w) // 2
    draw.rounded_rectangle((lx, y + th + 14, lx + line_w, y + th + 18), radius=2, fill=(*PRIMARY, 200))
    return y + th + 40


def glass_card(base: Image.Image, box, radius=36):
    """Frosted white card like site glass/sheet."""
    x0, y0, x1, y1 = box
    card = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(card)
    # soft shadow
    shadow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow)
    sd.rounded_rectangle((x0, y0 + 10, x1, y1 + 14), radius=radius, fill=(43, 33, 35, 28))
    shadow = shadow.filter(ImageFilter.GaussianBlur(18))
    base.alpha_composite(shadow)
    d.rounded_rectangle(box, radius=radius, fill=(255, 255, 255, 210))
    d.rounded_rectangle(box, radius=radius, outline=(43, 33, 35, 18), width=2)
    base.alpha_composite(card)


def primary_pill(draw, text, y):
    font = F(FONT_SEMI, 28)
    tw, th = text_wh(draw, text, font)
    pad_x, pad_y = 36, 18
    box = ((W - tw) // 2 - pad_x, y, (W + tw) // 2 + pad_x, y + th + pad_y * 2)
    draw.rounded_rectangle(box, radius=999, fill=PRIMARY)
    draw_text(draw, text, ((W - tw) // 2, y + pad_y), font, WHITE)
    return box[3]


def slide1(base: Image.Image) -> Image.Image:
    img = base.copy()
    draw = ImageDraw.Draw(img, "RGBA")
    brand_header(draw, img)

    y = centered_lines(
        draw,
        ["5 мифов,", "которые мешают", "получить тело мечты"],
        420,
        F(FONT_SEMI, 64),
        (*INK, 250),
        gap=1.12,
    )
    # spacer bar well below title block
    draw.rounded_rectangle((W // 2 - 36, y + 36, W // 2 + 36, y + 42), radius=3, fill=PRIMARY)

    centered_lines(
        draw,
        ["Спойлер: ты веришь", "минимум в три из них"],
        y + 90,
        F(FONT_REG, 36),
        MUTED,
        gap=1.25,
    )

    hint = "листай →"
    font = F(FONT_MED, 28)
    tw, _ = text_wh(draw, hint, font)
    draw_text(draw, hint, ((W - tw) // 2, H - 200), font, (*PRIMARY_EMPH, 200))
    return img


def slide2(base: Image.Image) -> Image.Image:
    img = base.copy()
    draw = ImageDraw.Draw(img, "RGBA")
    brand_header(draw, img)

    centered_lines(draw, ["Три мифа,"], 250, F(FONT_SEMI, 52), (*INK, 250))
    centered_lines(draw, ["которые тормозят результат"], 330, F(FONT_REG, 30), MUTED)

    myths = [
        ("«Нужно просто меньше есть»", "Это путь к срывам — не к форме."),
        ("«Сон не важен»", "Больше сна = больше сил\nи меньше тяги к сладкому."),
        ("«Без спорта никак»", "Бытовые привычки дают\nдо 70% результата."),
    ]

    y = 420
    for title, body in myths:
        lines = body.split("\n")
        h = 210 + (len(lines) - 1) * 36
        glass_card(img, (64, y, W - 64, y + h), radius=28)
        draw = ImageDraw.Draw(img, "RGBA")
        # primary cross
        draw_text(draw, "×", (96, y + 42), F(FONT_SEMI, 44), PRIMARY)
        draw_text(draw, title, (160, y + 52), F(FONT_SEMI, 30), (*INK, 245))
        by = y + 110
        for line in lines:
            draw_text(draw, line, (160, by), F(FONT_REG, 28), MUTED)
            by += 38
        y += h + 28

    return img


def slide3(base: Image.Image) -> Image.Image:
    img = base.copy()
    draw = ImageDraw.Draw(img, "RGBA")
    brand_header(draw, img)

    centered_lines(draw, ["Как я это исправляю"], 260, F(FONT_SEMI, 48), (*INK, 250))

    blocks = [
        ("01", "Аудит за 3 дня", "PDF с чёткими рекомендациями:\nчто поправить быстро."),
        ("02", "Ведение 1 месяц", "7 дней анализа + поддержка каждый день.\nНастроим сон и простые привычки."),
    ]
    y = 400
    for num, title, body in blocks:
        glass_card(img, (64, y, W - 64, y + 400), radius=36)
        draw = ImageDraw.Draw(img, "RGBA")
        draw_text(draw, num, (110, y + 48), F(FONT_SEMI, 56), PRIMARY)
        draw_text(draw, title, (110, y + 140), F(FONT_SEMI, 40), (*INK, 250))
        by = y + 220
        for line in body.split("\n"):
            draw_text(draw, line, (110, by), F(FONT_REG, 30), MUTED)
            by += 44
        y += 440

    return img


def slide4(base: Image.Image) -> Image.Image:
    img = base.copy()
    draw = ImageDraw.Draw(img, "RGBA")
    brand_header(draw, img)

    centered_lines(draw, ["Только 3 места"], 280, F(FONT_SEMI, 58), (*INK, 250))
    centered_lines(
        draw,
        ["Все — бесплатно", "за отзыв и фото «до / после»"],
        380,
        F(FONT_REG, 34),
        MUTED,
        gap=1.25,
    )

    glass_card(img, (100, 620, W - 100, 1120), radius=36)
    draw = ImageDraw.Draw(img, "RGBA")
    centered_lines(draw, ["Напиши в Директ"], 680, F(FONT_REG, 32), MUTED)
    centered_lines(draw, ["кодовое слово"], 740, F(FONT_REG, 32), MUTED)
    centered_lines(draw, ["МИФ"], 840, F(FONT_SEMI, 110), PRIMARY)

    primary_pill(draw, "написать «МИФ»", 1200)

    centered_lines(
        draw,
        ["Пришлю разбор — какой миф", "тормозит именно тебя"],
        1340,
        F(FONT_REG, 30),
        SECONDARY,
    )
    return img


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    ART.mkdir(parents=True, exist_ok=True)
    base = make_bg()

    slides = [
        ("01-hook.png", slide1),
        ("02-myths.png", slide2),
        ("03-solution.png", slide3),
        ("04-cta-mif.png", slide4),
    ]
    for name, fn in slides:
        img = fn(base).convert("RGB")
        path = OUT / name
        img.save(path, "PNG", optimize=True)
        (ART / name).write_bytes(path.read_bytes())
        jpg = ART / name.replace(".png", ".jpg")
        img.save(jpg, "JPEG", quality=93, optimize=True)
        print("OK", name)


if __name__ == "__main__":
    main()
