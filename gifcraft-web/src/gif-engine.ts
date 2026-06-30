import GIF from 'gif.js';
import type { GifProject, TemplateDef, TextAnimation } from './types';
import { TEMPLATES } from './types';

export interface ExportProgress {
  phase: 'frames' | 'encode';
  value: number;
}

function getTemplate(id: string): TemplateDef {
  return TEMPLATES.find((t) => t.id === id) ?? TEMPLATES[0];
}

function drawTextOverlay(
  ctx: CanvasRenderingContext2D,
  project: GifProject,
  frameIndex: number,
  totalFrames: number,
  w: number,
  h: number,
): void {
  const text = project.text.trim();
  if (!text) return;

  const tpl = getTemplate(project.templateId);
  const progress = frameIndex / Math.max(totalFrames - 1, 1);

  ctx.save();
  ctx.font = `${tpl.fontWeight} ${tpl.fontSize}px Inter, system-ui, sans-serif`;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';

  let alpha = 1;
  let offsetX = 0;
  switch (project.textAnimation as TextAnimation) {
    case 'fade':
      alpha = Math.min(1, progress * 2.5);
      break;
    case 'blink':
      alpha = frameIndex % 6 < 3 ? 1 : 0.35;
      break;
    case 'slide':
      offsetX = Math.min(w * 0.15, progress * w * 0.2);
      break;
    default:
      break;
  }

  const metrics = ctx.measureText(text);
  const padX = 14;
  const padY = 10;
  const boxW = metrics.width + padX * 2;
  const boxH = tpl.fontSize + padY * 2;
  const x = w / 2 + offsetX;
  const y = h * 0.85;

  if (tpl.bg !== 'transparent') {
    ctx.globalAlpha = alpha;
    ctx.fillStyle = tpl.bg;
    const r = 8;
    const left = x - boxW / 2;
    const top = y - boxH / 2;
    ctx.beginPath();
    ctx.moveTo(left + r, top);
    ctx.lineTo(left + boxW - r, top);
    ctx.quadraticCurveTo(left + boxW, top, left + boxW, top + r);
    ctx.lineTo(left + boxW, top + boxH - r);
    ctx.quadraticCurveTo(left + boxW, top + boxH, left + boxW - r, top + boxH);
    ctx.lineTo(left + r, top + boxH);
    ctx.quadraticCurveTo(left, top + boxH, left, top + boxH - r);
    ctx.lineTo(left, top + r);
    ctx.quadraticCurveTo(left, top, left + r, top);
    ctx.closePath();
    ctx.fill();
  }

  ctx.globalAlpha = alpha;
  ctx.fillStyle = project.textColor || tpl.color;
  ctx.fillText(text, x, y);
  ctx.restore();

  ctx.globalAlpha = 1;
}

async function loadImage(src: string): Promise<HTMLImageElement> {
  const img = new Image();
  img.crossOrigin = 'anonymous';
  img.src = src;
  await img.decode();
  return img;
}

async function extractVideoFrames(
  url: string,
  project: GifProject,
  onProgress: (p: number) => void,
): Promise<HTMLCanvasElement[]> {
  const video = document.createElement('video');
  video.src = url;
  video.muted = true;
  video.playsInline = true;
  await new Promise<void>((resolve, reject) => {
    video.onloadedmetadata = () => resolve();
    video.onerror = () => reject(new Error('Video load failed'));
  });

  const scale = project.width / video.videoWidth;
  const w = project.width;
  const h = Math.max(1, Math.round(video.videoHeight * scale));
  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;
  const ctx = canvas.getContext('2d')!;

  const start = project.trimStart;
  const end = project.trimEnd;
  const step = 1 / project.fps;
  const frames: HTMLCanvasElement[] = [];
  const total = Math.ceil((end - start) * project.fps);

  let i = 0;
  for (let t = start; t < end; t += step) {
    video.currentTime = Math.min(t, video.duration - 0.01);
    await new Promise<void>((r) => {
      video.onseeked = () => r();
    });
    ctx.drawImage(video, 0, 0, w, h);
    drawTextOverlay(ctx, project, i, total, w, h);

    const frameCanvas = document.createElement('canvas');
    frameCanvas.width = w;
    frameCanvas.height = h;
    frameCanvas.getContext('2d')!.drawImage(canvas, 0, 0);
    frames.push(frameCanvas);

    i += 1;
    onProgress(i / total);
  }

  return frames;
}

async function extractPhotoFrames(
  urls: string[],
  project: GifProject,
  onProgress: (p: number) => void,
): Promise<HTMLCanvasElement[]> {
  const images = await Promise.all(urls.map(loadImage));
  const first = images[0];
  const scale = project.width / first.naturalWidth;
  const w = project.width;
  const h = Math.max(1, Math.round(first.naturalHeight * scale));

  const duration = project.trimEnd - project.trimStart;
  const perPhoto = duration / images.length;
  const framesPerPhoto = Math.max(1, Math.round(perPhoto * project.fps));
  const frames: HTMLCanvasElement[] = [];
  const total = images.length * framesPerPhoto;
  let idx = 0;

  for (const img of images) {
    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(img, 0, 0, w, h);

    for (let f = 0; f < framesPerPhoto; f++) {
      drawTextOverlay(ctx, project, idx, total, w, h);
      const frameCanvas = document.createElement('canvas');
      frameCanvas.width = w;
      frameCanvas.height = h;
      frameCanvas.getContext('2d')!.drawImage(canvas, 0, 0);
      frames.push(frameCanvas);
      idx += 1;
      onProgress(idx / total);
    }
  }

  return frames;
}

export async function buildGifBlob(
  project: GifProject,
  onProgress: (p: ExportProgress) => void,
): Promise<Blob> {
  onProgress({ phase: 'frames', value: 0 });

  const frameProgress = (v: number) =>
    onProgress({ phase: 'frames', value: v });

  let frames: HTMLCanvasElement[];
  if (project.sourceType === 'photos') {
    frames = await extractPhotoFrames(project.mediaUrls, project, frameProgress);
  } else {
    const url = project.mediaUrls[0];
    if (!url) throw new Error('No media');
    frames = await extractVideoFrames(url, project, frameProgress);
  }

  if (frames.length === 0) throw new Error('No frames');

  const delay = Math.round(1000 / project.fps);
  const workerUrl = new URL('gif.js/dist/gif.worker.js', import.meta.url).href;

  const gif = new GIF({
    workers: 2,
    quality: 12,
    width: frames[0].width,
    height: frames[0].height,
    workerScript: workerUrl,
  });

  for (const frame of frames) {
    gif.addFrame(frame, { copy: true, delay });
  }

  return new Promise((resolve, reject) => {
    gif.on('progress', (p: number) => {
      onProgress({ phase: 'encode', value: p });
    });
    gif.on('finished', (blob: Blob) => resolve(blob));
    gif.on('error', (e: Error) => reject(e));
    gif.render();
  });
}

export async function getVideoDuration(url: string): Promise<number> {
  const video = document.createElement('video');
  video.src = url;
  video.muted = true;
  await new Promise<void>((resolve, reject) => {
    video.onloadedmetadata = () => resolve();
    video.onerror = () => reject(new Error('Video metadata failed'));
  });
  return video.duration;
}
