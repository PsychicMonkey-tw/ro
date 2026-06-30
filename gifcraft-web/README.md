# GifCraft Web

Browser-based GIF maker: video, photo slideshow, or camera → trim → editor → export.

## Stack

- Vite + TypeScript
- gif.js for encoding
- Canvas API for frames and text overlay
- localStorage for draft metadata

## Run locally

```bash
cd gifcraft-web
npm install
npm run dev
```

Open the URL shown in terminal (works on phone in same Wi‑Fi network).

## Build

```bash
npm run build
npm run preview
```

Output in `dist/` — deploy to any static host (Netlify, Vercel, nginx, GitHub Pages).

## Features

- Video / photos / camera sources
- Trim, FPS, width
- Text, colors, templates, animations
- Download or share GIF (Web Share API on mobile)
- Russian + English UI
