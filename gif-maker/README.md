# GifCraft

Cross-platform mobile app (Android + iOS) for creating GIFs from video, photo slideshows, or camera recordings.

## Features (v1)

- Import from gallery video, multiple photos, or camera recording
- Trim clip, adjust FPS and width
- Text overlay with animations (fade, blink, slide)
- Templates and color palette
- Export to GIF / MP4 / WebP (full version)
- Russian and English UI
- One-time in-app purchase (no accounts, all local)

## Requirements

- Flutter 3.x
- Xcode (iOS builds)
- Android Studio / SDK (Android builds)

## Setup

```bash
cd gif-maker
flutter pub get
flutter gen-l10n
```

## Run

```bash
flutter run
```

## Store configuration

Configure non-consumable product ID `gifcraft_full_unlock` in:

- Google Play Console (Android)
- App Store Connect (iOS)

Product IDs are defined in `lib/services/purchase_service.dart`.

In debug builds, the purchase button unlocks the full version when store products are unavailable.

## Project structure

```
lib/
  models/       # GifProject, TextLayer, templates
  services/     # Projects, export (FFmpeg), purchases, locale
  screens/      # Home, source, trim, editor, export, purchase
  l10n/         # Russian and English strings
```

## Permissions

The app requests camera, microphone, and photo library access for capture and export.
