#!/usr/bin/env python3
"""Build 4 vertical myth-busting Instagram/YouTube/Rutube shorts."""

from __future__ import annotations

import asyncio
import math
import os
import subprocess
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont
import edge_tts

ROOT = Path("/workspace/shorts")
AUDIO = ROOT / "audio"
FRAMES = ROOT / "frames"
OUT = ROOT / "out"
ART = Path("/opt/cursor/artifacts/shorts")
ASSETS = Path("/opt/cursor/artifacts/assets")

W, H = 1080, 1920
VOICE = "ru-RU-SvetlanaNeural"
FONT_BOLD = "/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf"
FONT_REG = "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf"

SHORTS = [
    {
        "id": "01-mif-menshe-est",
        "title": "Миф 1: просто меньше есть",
        "bg": "shorts-bg-myth.png",
        "voice": (
            "Миф номер один: нужно просто меньше есть. "
            "Звучит логично. На деле — это прямой путь к срывам. "
            "Ты режешь калории — организм орёт — вечером срывает на сладкое — "
            "вина — снова режешь. Круг. "
            "Меньше есть — не стратегия. Это ловушка. "
            "Часть два — про сон. А какой миф твой — напиши МИФ."
        ),
        "scenes": [
            (0.00, 0.18, "МИФ 1 / 3", "❌ «Просто меньше есть»", "Это путь к срывам"),
            (0.18, 0.55, "режешь → срыв → вина", "→ снова режешь", "Замкнутый круг"),
            (0.55, 0.82, "Меньше есть", "≠ стратегия", "Это ловушка"),
            (0.82, 1.00, "Часть 2 — про сон", "Пиши кодовое:", "МИФ"),
        ],
    },
    {
        "id": "02-mif-son",
        "title": "Миф 2: сон не важен",
        "bg": "shorts-bg-myth.png",
        "voice": (
            "Миф номер два: сон не важен, главное — диета и спорт. "
            "Нет. Мало спал — сил нет, тянет к сладкому, решения слабее. "
            "Больше сна — больше энергии и меньше вечеринков с шоколадом. "
            "Если цель — тело, сон — не опция. Это база. "
            "Часть три — миф про спорт. Пиши МИФ — разберу твой."
        ),
        "scenes": [
            (0.00, 0.20, "МИФ 2 / 3", "❌ «Сон не важен»", "Главное — не только еда"),
            (0.20, 0.50, "мало сна", "→ тяга к сладкому", "и меньше сил"),
            (0.50, 0.80, "больше сна", "= больше энергии", "Сон — это база"),
            (0.80, 1.00, "Часть 3 — про спорт", "Пиши кодовое:", "МИФ"),
        ],
    },
    {
        "id": "03-mif-sport",
        "title": "Миф 3: без спорта никак",
        "bg": "shorts-bg-myth.png",
        "voice": (
            "Миф номер три: без спорта тело мечты не получить. "
            "Спорт помогает. Но бытовые привычки дают до семидесяти процентов результата. "
            "Сон. Режим еды. Стресс. Шаги. Вода. "
            "Пока это хаос — даже идеальная тренировка тянет слабо. "
            "Начни с привычек — тело догонит. "
            "В следующем ролике — как я это исправляю. И три бесплатных места."
        ),
        "scenes": [
            (0.00, 0.18, "МИФ 3 / 3", "❌ «Без спорта никак»", "Спорт ≠ всё"),
            (0.18, 0.48, "Бытовые привычки", "дают до 70%", "результата"),
            (0.48, 0.75, "Сон · еда · стресс", "шаги · вода", "Сначала база"),
            (0.75, 1.00, "Дальше — решение", "и 3 бесплатных", "места"),
        ],
    },
    {
        "id": "04-offer-mif",
        "title": "Оффер: пиши МИФ",
        "bg": "shorts-bg-offer.png",
        "voice": (
            "Ты уже видел три мифа: меньше есть, сон не важен, без спорта никак. "
            "Как я это исправляю. "
            "Аудит за три дня — пи-ди-эф с чёткими рекомендациями: что поправить быстро. "
            "Ведение один месяц — семь дней анализа и поддержка каждый день. "
            "Настроим сон и простые привычки. "
            "Только три места. Все — бесплатно за отзыв и фото до и после. "
            "Пиши кодовое слово МИФ — пришлю разбор, какой миф тормозит именно тебя."
        ),
        "scenes": [
            (0.00, 0.18, "3 мифа — позади", "Как я это", "исправляю"),
            (0.18, 0.42, "🔹 Аудит за 3 дня", "PDF с тем,", "что поправить быстро"),
            (0.42, 0.68, "🔹 Ведение 1 месяц", "7 дней анализа", "+ поддержка каждый день"),
            (0.68, 0.85, "Только 3 места", "Бесплатно", "отзыв + фото до/после"),
            (0.85, 1.00, "Пиши кодовое слово", "МИФ", "Разберу твой миф"),
        ],
    },
]


def font(size: int, bold: bool = True) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(FONT_BOLD if bold else FONT_REG, size)


def load_bg(name: str) -> Image.Image:
    path = ASSETS / name
    img = Image.open(path).convert("RGB")
    img = img.resize((W, H), Image.Resampling.LANCZOS)
    # Darken for readable captions
    overlay = Image.new("RGB", (W, H), (8, 12, 18))
    return Image.blend(img, overlay, 0.45)


def rounded_panel(draw: ImageDraw.ImageDraw, box, fill, radius=36):
    draw.rounded_rectangle(box, radius=radius, fill=fill)


def draw_scene(base: Image.Image, line1: str, line2: str, line3: str, progress: float) -> Image.Image:
    img = base.copy()
    draw = ImageDraw.Draw(img, "RGBA")

    # Top progress bar
    bar_y = 96
    draw.rounded_rectangle((80, bar_y, W - 80, bar_y + 10), radius=6, fill=(255, 255, 255, 40))
    filled = int(80 + (W - 160) * min(max(progress, 0.05), 1.0))
    draw.rounded_rectangle((80, bar_y, filled, bar_y + 10), radius=6, fill=(255, 214, 102, 230))

    # Brand chip
    chip = "5 МИФОВ · ТЕЛО МЕЧТЫ"
    f_chip = font(28, bold=True)
    bbox = draw.textbbox((0, 0), chip, font=f_chip)
    tw = bbox[2] - bbox[0]
    chip_x = (W - tw) // 2
    rounded_panel(draw, (chip_x - 28, 140, chip_x + tw + 28, 200), (0, 0, 0, 140), 24)
    draw.text((chip_x, 152), chip, font=f_chip, fill=(255, 255, 255, 230))

    # Main caption panel
    panel = (70, 620, W - 70, 1380)
    rounded_panel(draw, panel, (10, 14, 20, 185), 48)

    def center_text(text: str, y: int, size: int, fill, bold=True, max_width=18):
        f = font(size, bold=bold)
        lines = textwrap.wrap(text, width=max_width) or [text]
        for i, line in enumerate(lines):
            bbox = draw.textbbox((0, 0), line, font=f)
            tw = bbox[2] - bbox[0]
            draw.text(((W - tw) // 2, y + i * int(size * 1.2)), line, font=f, fill=fill)

    center_text(line1, 700, 54, (255, 214, 102, 255), True, 22)
    center_text(line2, 860, 72, (255, 255, 255, 255), True, 16)
    center_text(line3, 1100, 56, (210, 230, 240, 255), False, 20)

    # Bottom safe zone hint
    tip = "Instagram · YouTube · Rutube"
    f_tip = font(26, bold=False)
    bbox = draw.textbbox((0, 0), tip, font=f_tip)
    tw = bbox[2] - bbox[0]
    draw.text(((W - tw) // 2, H - 160), tip, font=f_tip, fill=(255, 255, 255, 120))

    return img.convert("RGB")


async def synth(text: str, path: Path):
    # Prefer CLI — more reliable with network/rate quirks than the Python API.
    edge = os.path.expanduser("~/.local/bin/edge-tts")
    if not os.path.exists(edge):
        edge = "edge-tts"
    try:
        subprocess.check_call(
            [
                edge,
                "--voice",
                VOICE,
                "--rate=+6%",
                "--text",
                text,
                "--write-media",
                str(path),
            ],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
        if path.exists() and path.stat().st_size > 1000:
            return
    except Exception:
        pass
    communicate = edge_tts.Communicate(text, VOICE)
    await communicate.save(str(path))


def audio_duration(path: Path) -> float:
    out = subprocess.check_output(
        [
            "ffprobe",
            "-v",
            "error",
            "-show_entries",
            "format=duration",
            "-of",
            "default=noprint_wrappers=1:nokey=1",
            str(path),
        ],
        text=True,
    ).strip()
    return float(out)


def render_short(item: dict):
    sid = item["id"]
    audio = AUDIO / f"{sid}.mp3"
    duration = audio_duration(audio)
    bg = load_bg(item["bg"])

    scene_dir = FRAMES / sid
    scene_dir.mkdir(parents=True, exist_ok=True)

    # Build scene images + concat list with durations
    concat_path = scene_dir / "concat.txt"
    lines = []
    for idx, (start_r, end_r, a, b, c) in enumerate(item["scenes"]):
        mid = (start_r + end_r) / 2
        frame = draw_scene(bg, a, b, c, end_r)
        frame_path = scene_dir / f"scene_{idx:02d}.png"
        frame.save(frame_path, optimize=True)
        seg_dur = max((end_r - start_r) * duration, 0.8)
        lines.append(f"file '{frame_path}'")
        lines.append(f"duration {seg_dur:.3f}")
    # last frame repeat for concat demuxer
    lines.append(f"file '{scene_dir / f'scene_{len(item['scenes'])-1:02d}.png'}'")
    concat_path.write_text("\n".join(lines) + "\n")

    silent_video = OUT / f"{sid}_silent.mp4"
    final_video = OUT / f"{sid}.mp4"
    art_video = ART / f"{sid}.mp4"

    # Image slideshow → video
    subprocess.check_call(
        [
            "ffmpeg",
            "-y",
            "-f",
            "concat",
            "-safe",
            "0",
            "-i",
            str(concat_path),
            "-vf",
            f"scale={W}:{H}:force_original_aspect_ratio=decrease,pad={W}:{H}:(ow-iw)/2:(oh-ih)/2,fps=30,format=yuv420p",
            "-c:v",
            "libx264",
            "-pix_fmt",
            "yuv420p",
            "-movflags",
            "+faststart",
            str(silent_video),
        ],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    # Mux audio, trim/pad to audio length
    subprocess.check_call(
        [
            "ffmpeg",
            "-y",
            "-i",
            str(silent_video),
            "-i",
            str(audio),
            "-filter_complex",
            f"[0:v]trim=duration={duration:.3f},setpts=PTS-STARTPTS,fade=t=in:st=0:d=0.35,fade=t=out:st={max(duration-0.45,0):.3f}:d=0.45[v];"
            f"[1:a]afade=t=in:st=0:d=0.15,afade=t=out:st={max(duration-0.35,0):.3f}:d=0.35[a]",
            "-map",
            "[v]",
            "-map",
            "[a]",
            "-c:v",
            "libx264",
            "-preset",
            "medium",
            "-crf",
            "18",
            "-c:a",
            "aac",
            "-b:a",
            "192k",
            "-shortest",
            "-movflags",
            "+faststart",
            str(final_video),
        ],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    art_video.write_bytes(final_video.read_bytes())
    print(f"OK {sid} ({duration:.1f}s) → {final_video}")


async def main():
    ART.mkdir(parents=True, exist_ok=True)
    OUT.mkdir(parents=True, exist_ok=True)
    AUDIO.mkdir(parents=True, exist_ok=True)

    print("Synthesizing voiceovers...")
    for item in SHORTS:
        path = AUDIO / f"{item['id']}.mp3"
        await synth(item["voice"], path)
        print(f"  audio {item['id']}: {audio_duration(path):.1f}s")

    print("Rendering videos...")
    for item in SHORTS:
        render_short(item)

    # README for publishing
    readme = ROOT / "README.md"
    readme.write_text(
        textwrap.dedent(
            """\
            # Шортсы: 5 мифов (серия 4 ролика)

            Готовые вертикальные ролики 1080×1920 (9:16) для Instagram Reels, YouTube Shorts, Rutube Shorts.

            ## Файлы

            | Файл | Тема | CTA |
            |------|------|-----|
            | `out/01-mif-menshe-est.mp4` | Миф «меньше есть» | МИФ |
            | `out/02-mif-son.mp4` | Миф «сон не важен» | МИФ |
            | `out/03-mif-sport.mp4` | Миф «без спорта никак» | тизер оффера |
            | `out/04-offer-mif.mp4` | Аудит + ведение + 3 места | **МИФ** |

            Копии также в `/opt/cursor/artifacts/shorts/`.

            ## Куда вести

            - Instagram: Директ «МИФ»
            - YouTube: комментарий «МИФ»
            - Rutube: комментарий / сообщения «МИФ»

            ## Порядок публикации

            День 1 → ролик 1 · День 2 → ролик 2 · День 3 → ролик 3 · День 4 → ролик 4 (закрепить)
            """
        ),
        encoding="utf-8",
    )
    print("Done.")


if __name__ == "__main__":
    asyncio.run(main())
