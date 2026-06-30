# GifCraft — Design Specification

**Date:** 2026-06-30  
**Status:** Approved  
**Platforms:** Android + iOS (Flutter)  
**Languages:** Russian, English

## Overview

GifCraft is a cross-platform mobile app for creating GIFs from video, photo slideshows, or camera recordings. All processing and storage is local — no user accounts or cloud sync.

## Requirements

| Parameter | Decision |
|-----------|----------|
| Sources | Video file, multiple photos, camera recording |
| Editor | Advanced: layers, animated text, color palette, templates |
| Export | GIF, MP4, WebP |
| Accounts | None — local only |
| Monetization | One-time purchase (App Store / Google Play) |

## Architecture

```
UI Screens → Business Logic (Project/Export/IAP) → SQLite + File Storage → FFmpeg
```

- **Projects:** SQLite for metadata (layers, text, settings); media files on device filesystem.
- **Purchase state:** Secure Storage (Keychain / EncryptedSharedPreferences).
- **Encoding:** ffmpeg_kit_flutter for GIF, MP4, WebP export.

## Screens

1. **Home** — New GIF, draft list, purchase CTA if demo.
2. **Source picker** — Video / Photos / Camera.
3. **Trim** — Fragment selection, duration, FPS, dimensions.
4. **Editor** — Canvas with layers (background, text, stickers, templates).
5. **Export** — Format, quality, preview, save to gallery / share.
6. **Purchase** — One-time IAP screen.

## Monetization (Demo vs Full)

| Demo (free) | Full (one-time purchase) |
|-------------|--------------------------|
| 1 project, watermark | Unlimited projects |
| GIF export only, low quality | GIF + MP4 + WebP, full quality |
| 3 basic templates | All templates and text animations |

## Implementation Phases

- **v1 (MVP):** Sources, trim, basic text overlay, GIF/MP4 export, IAP scaffold, ru/en i18n.
- **v2:** Layers, animated text, templates, WebP, color palette.
- **v3:** Stickers, frames, undo/redo polish.

## Tech Stack

- Flutter 3.x / Dart 3.x
- ffmpeg_kit_flutter — media encoding
- image_picker, camera — media input
- sqflite — project database
- flutter_secure_storage — purchase flag
- in_app_purchase — store billing
- flutter_localizations + intl — ru/en

## Project Location

`/gif-maker/` — separate from the PHP catalog site in repo root.
