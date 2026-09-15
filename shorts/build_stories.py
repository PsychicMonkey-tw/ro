#!/usr/bin/env python3
"""Beautiful Instagram Stories slides — images only, no video/audio."""

from __future__ import annotations

import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageEnhance, ImageFilter, ImageFont

W, H = 1080, 1920
ASSETS = Path("/opt/cursor/artifacts/assets")
OUT = Path("/workspace/shorts/stories")
ART = Path("/opt/cursor/artifacts/stories")

FONT_DISPLAY = "/usr/share/fonts/truetype/noto/NotoSerifDisplay-Bold.ttf"
FONT_DISPLAY_REG = "/usr/share/fonts/truetype/noto/NotoSerifDisplay-Regular.ttf"
FONT_SANS = "/usr/share/fonts/truetype/noto/NotoSansDisplay-Regular.ttf"
FONT_SANS_BOLD = "/usr/share/fonts/truetype/noto/NotoSansDisplay-Bold.ttf"

# Palette: deep green-charcoal + champagne gold (not purple, not cream-terracotta)
GOLD = (232, 201, 138)
GOLD_SOFT = (210, 185, 130)
WHITE = (248, 246, 242)
MUTED = (190, 198, 196)
CROSS = (232, 120, 110)
INK = (12, 16, 18)


def F(path: str, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(path, size)


def prepare_bg(name: str, darken: float = 0.52) -> Image.Image:
    img = Image.open(ASSETS / name).convert("RGB").resize((W, H), Image.Resampling.LANCZOS)
    img = ImageEnhance.Contrast(img).enhance(1.08)
    img = ImageEnhance.Color(img).enhance(0.85)
    veil = Image.new("RGB", (W, H), INK)
    img = Image.blend(img, veil, darken)
    # soft vignette
    vig = Image.new("L", (W, H), 0)
    vd = ImageDraw.Draw(vig)
    for i in range(80):
        a = int(180 * (i / 80) ** 1.6)
        vd.rectangle((i * 4, i * 6, W - i * 4, H - i * 6), outline=a)
    vig = vig.filter(ImageFilter.GaussianBlur(40))
    black = Image.new("RGB", (W, H), (0, 0, 0))
    img = Image.composite(img, Image.blend(img, black, 0.55), Image.eval(vig, lambda x: 255 - x))
    return img


def text_size(draw, text, font):
    b = draw.textbbox((0, 0), text, font=font)
    return b[2] - b[0], b[3] - b[1]


def draw_centered(draw, text, y, font, fill, max_width=None):
    if max_width:
        # approximate wrap by chars
        avg = max(font.size * 0.52, 1)
        width_chars = max(int(max_width / avg), 8)
        lines = textwrap.wrap(text, width=width_chars) or [text]
    else:
        lines = text.split("\n")
    cy = y
    for line in lines:
        tw, th = text_size(draw, line, font)
        draw.text(((W - tw) // 2, cy), line, font=font, fill=fill)
        cy += int(th * 1.28)
    return cy


def hairline(draw, y, pad=120, color=(255, 255, 255, 55)):
    draw.line((pad, y, W - pad, y), fill=color, width=2)


def slide1():
    base = prepare_bg("story-bg-1.png", 0.48).convert("RGBA")
    draw = ImageDraw.Draw(base, "RGBA")

    # top label
    label = "ДЛЯ НОВОЙ АУДИТОРИИ"
    f_label = F(FONT_SANS, 26)
    tw, _ = text_size(draw, label, f_label)
    draw.rounded_rectangle(
        ((W - tw) // 2 - 28, 160, (W - tw) // 2 + tw + 28, 220),
        radius=22,
        fill=(0, 0, 0, 120),
        outline=(*GOLD, 160),
        width=2,
    )
    draw.text(((W - tw) // 2, 174), label, font=f_label, fill=GOLD)

    y = draw_centered(draw, "5 мифов,", 480, F(FONT_DISPLAY, 92), WHITE, max_width=900)
    y = draw_centered(draw, "которые мешают", y + 8, F(FONT_DISPLAY, 78), WHITE, max_width=920)
    y = draw_centered(draw, "получить тело мечты", y + 8, F(FONT_DISPLAY, 78), GOLD, max_width=920)

    hairline(draw, y + 48)

    draw_centered(
        draw,
        "Спойлер: ты веришь\nминимум в три из них",
        y + 80,
        F(FONT_SANS, 40),
        MUTED,
    )

    # bottom swipe hint
    hint = "листай →"
    f = F(FONT_SANS, 28)
    tw, _ = text_size(draw, hint, f)
    draw.text(((W - tw) // 2, H - 180), hint, font=f, fill=(255, 255, 255, 130))

    return base.convert("RGB")


def slide2():
    base = prepare_bg("story-bg-2.png", 0.55).convert("RGBA")
    draw = ImageDraw.Draw(base, "RGBA")

    draw_centered(draw, "Три мифа,", 200, F(FONT_DISPLAY, 64), WHITE)
    draw_centered(draw, "которые тормозят результат", 290, F(FONT_SANS, 34), MUTED)

    myths = [
        ("«Нужно просто меньше есть»", "Это путь к срывам — не к форме."),
        ("«Сон не важен»", "Больше сна = больше сил\nи меньше тяги к сладкому."),
        ("«Без спорта никак»", "Бытовые привычки дают\nдо 70% результата."),
    ]

    y = 420
    for title, body in myths:
        # subtle panel — interaction-like but light
        panel_h = 320 if "\n" in body else 280
        draw.rounded_rectangle((70, y, W - 70, y + panel_h), radius=32, fill=(8, 12, 14, 160))
        # red cross mark as typography, not emoji clutter
        cross = "×"
        f_cross = F(FONT_DISPLAY, 58)
        draw.text((100, y + 36), cross, font=f_cross, fill=CROSS)
        draw.text((170, y + 48), title, font=F(FONT_SANS_BOLD, 34), fill=WHITE)

        by = y + 120
        for line in body.split("\n"):
            draw.text((170, by), line, font=F(FONT_SANS, 30), fill=MUTED)
            by += 42
        y += panel_h + 36

    return base.convert("RGB")


def slide3():
    base = prepare_bg("story-bg-3.png", 0.56).convert("RGBA")
    draw = ImageDraw.Draw(base, "RGBA")

    draw_centered(draw, "Как я это исправляю", 220, F(FONT_DISPLAY, 62), WHITE)
    hairline(draw, 340)

    blocks = [
        ("01", "Аудит за 3 дня", "PDF с чёткими рекомендациями:\nчто поправить быстро."),
        ("02", "Ведение 1 месяц", "7 дней анализа + поддержка каждый день.\nНастроим сон и простые привычки."),
    ]

    y = 420
    for num, title, body in blocks:
        draw.rounded_rectangle((70, y, W - 70, y + 420), radius=36, fill=(8, 12, 14, 168))
        draw.text((110, y + 48), num, font=F(FONT_DISPLAY, 72), fill=GOLD)
        draw.text((110, y + 150), title, font=F(FONT_SANS_BOLD, 44), fill=WHITE)
        by = y + 230
        for line in body.split("\n"):
            draw.text((110, by), line, font=F(FONT_SANS, 32), fill=MUTED)
            by += 48
        y += 460

    return base.convert("RGB")


def slide4():
    base = prepare_bg("story-bg-4.png", 0.50).convert("RGBA")
    draw = ImageDraw.Draw(base, "RGBA")

    draw_centered(draw, "Только 3 места", 280, F(FONT_DISPLAY, 78), WHITE)
    draw_centered(
        draw,
        "Все — бесплатно\nза отзыв и фото «до / после»",
        400,
        F(FONT_SANS, 36),
        MUTED,
    )

    hairline(draw, 560)

    # Code word block — hero CTA
    box = (120, 640, W - 120, 1080)
    draw.rounded_rectangle(box, radius=40, fill=(0, 0, 0, 150), outline=(*GOLD, 200), width=3)
    draw_centered(draw, "Напиши в Директ", 690, F(FONT_SANS, 34), MUTED)
    draw_centered(draw, "кодовое слово", 750, F(FONT_SANS, 34), MUTED)
    draw_centered(draw, "МИФ", 860, F(FONT_DISPLAY, 120), GOLD)

    draw_centered(
        draw,
        "Пришлю разбор — какой миф\nтормозит именно тебя",
        1180,
        F(FONT_SANS, 34),
        WHITE,
    )

    return base.convert("RGB")


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    ART.mkdir(parents=True, exist_ok=True)

    slides = [
        ("01-hook.png", slide1),
        ("02-myths.png", slide2),
        ("03-solution.png", slide3),
        ("04-cta-mif.png", slide4),
    ]
    for name, fn in slides:
        img = fn()
        # light grain for premium feel
        noise = Image.effect_noise((W, H), 12).convert("L")
        img = Image.blend(img, Image.merge("RGB", (noise, noise, noise)), 0.04)
        path = OUT / name
        img.save(path, "PNG", optimize=True)
        art = ART / name
        art.write_bytes(path.read_bytes())
        # also jpeg for easy IG upload
        jpg = ART / name.replace(".png", ".jpg")
        img.convert("RGB").save(jpg, "JPEG", quality=92, optimize=True)
        print(f"OK {name}")


if __name__ == "__main__":
    main()
