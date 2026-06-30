import { buildGifBlob } from './gif-engine';
import { getLang, setLang, t, templateLabel } from './i18n';
import {
  createProject,
  downloadBlob,
  pickPhotos,
  pickVideo,
  recordCamera,
  revokeProject,
  shareBlob,
} from './media';
import { upsertDraftMeta } from './storage';
import type { GifProject, Lang, SourceType, TextAnimation } from './types';
import { TEMPLATES } from './types';

type View = 'home' | 'trim' | 'editor' | 'export';

const PALETTE = ['#ffffff', '#000000', '#22d3ee', '#f59e0b', '#ef4444', '#84cc16', '#a855f7'];

let project: GifProject | null = null;
let view: View = 'home';
let exportBlob: Blob | null = null;
let exportProgress = 0;
let exportPhase: 'frames' | 'encode' = 'frames';
let errorMsg = '';
let previewUrl = '';

const root = document.querySelector<HTMLDivElement>('#app')!;

export function mountApp(): void {
  render();
}

function render(): void {
  root.innerHTML = '';
  root.appendChild(buildShell());
}

function buildShell(): HTMLElement {
  const wrap = el('div', 'app-shell');

  const header = el('header', 'header');
  const title = el('h1', 'logo', t('appTitle'));
  const langBtn = el('button', 'icon-btn', '🌐');
  langBtn.title = t('language');
  langBtn.onclick = () => toggleLang();
  header.append(title, langBtn);

  const main = el('main', 'main');
  if (view === 'home') main.appendChild(buildHome());
  if (view === 'trim' && project) main.appendChild(buildTrim());
  if (view === 'editor' && project) main.appendChild(buildEditor());
  if (view === 'export') main.appendChild(buildExport());

  wrap.append(header, main);
  return wrap;
}

function buildHome(): HTMLElement {
  const box = el('div', 'stack');

  box.append(el('p', 'tagline', t('tagline')));

  const newBtn = el('button', 'btn primary', t('newGif'));
  newBtn.onclick = () => showSourcePicker();
  box.append(newBtn);

  box.append(el('h2', 'section-title', t('drafts')));
  box.append(el('p', 'muted', t('noDrafts')));

  return box;
}

function showSourcePicker(): void {
  const overlay = el('div', 'overlay');
  const modal = el('div', 'modal');
  modal.append(el('h2', '', t('chooseSource')));

  modal.append(
    sourceBtn(t('sourceVideo'), t('sourceVideoDesc'), '🎬', async () => {
      const files = await pickVideo();
      if (files.length) await startProject('video', files);
      overlay.remove();
    }),
  );
  modal.append(
    sourceBtn(t('sourcePhotos'), t('sourcePhotosDesc'), '🖼️', async () => {
      const files = await pickPhotos();
      if (files.length) await startProject('photos', files);
      overlay.remove();
    }),
  );
  modal.append(
    sourceBtn(t('sourceCamera'), t('sourceCameraDesc'), '📷', async () => {
      try {
        overlay.remove();
        errorMsg = '';
        const file = await recordCamera();
        await startProject('camera', [file]);
      } catch {
        errorMsg = t('error');
        render();
      }
    }),
  );

  const cancel = el('button', 'btn ghost', t('cancel'));
  cancel.onclick = () => overlay.remove();
  modal.append(cancel);

  overlay.append(modal);
  overlay.onclick = (e) => {
    if (e.target === overlay) overlay.remove();
  };
  document.body.append(overlay);
}

function sourceBtn(title: string, desc: string, icon: string, onClick: () => void): HTMLElement {
  const btn = el('button', 'source-card');
  btn.innerHTML = `<span class="source-icon">${icon}</span><div><strong>${title}</strong><p>${desc}</p></div>`;
  btn.onclick = onClick;
  return btn;
}

async function startProject(type: SourceType, files: File[]): Promise<void> {
  if (project) revokeProject(project);
  project = await createProject(type, files);
  view = 'trim';
  render();
}

function buildTrim(): HTMLElement {
  const p = project!;
  const box = el('div', 'stack');

  const back = el('button', 'btn ghost', `← ${t('back')}`);
  back.onclick = () => {
    view = 'home';
    render();
  };
  box.append(back);

  box.append(el('h2', 'section-title', t('trim')));

  if (p.sourceType !== 'photos') {
    const video = el('video', 'preview-video') as HTMLVideoElement;
    video.src = p.mediaUrls[0];
    video.controls = true;
    video.playsInline = true;
    video.muted = true;
    box.append(video);

    box.append(labelRange(t('duration'), p.trimStart, p.trimEnd, 0, Math.max(p.trimEnd, 0.5), (s, e) => {
      p.trimStart = s;
      p.trimEnd = Math.max(e, s + 0.3);
      render();
    }));
  } else {
    box.append(el('p', 'muted', `${p.mediaFiles.length} photos`));
  }

  box.append(slider(t('fps'), p.fps, 5, 20, 1, (v) => {
    p.fps = v;
    render();
  }));

  box.append(slider(t('width'), p.width, 240, 720, 40, (v) => {
    p.width = v;
    render();
  }));

  const next = el('button', 'btn primary', t('continue'));
  next.onclick = () => {
    view = 'editor';
    render();
  };
  box.append(next);

  return box;
}

function buildEditor(): HTMLElement {
  const p = project!;
  const box = el('div', 'stack');

  const back = el('button', 'btn ghost', `← ${t('back')}`);
  back.onclick = () => {
    view = 'trim';
    render();
  };
  box.append(back);

  box.append(el('h2', 'section-title', t('editor')));

  const canvasWrap = el('div', 'canvas-wrap');
  const canvas = el('canvas', 'preview-canvas') as HTMLCanvasElement;
  canvasWrap.append(canvas);
  box.append(canvasWrap);
  void drawPreview(canvas, p);

  const textInput = el('input', 'input') as HTMLInputElement;
  textInput.placeholder = t('textPlaceholder');
  textInput.value = p.text;
  textInput.oninput = () => {
    p.text = textInput.value;
    void drawPreview(canvas, p);
  };
  box.append(el('label', 'label', t('text')), textInput);

  box.append(el('p', 'label', t('animation')));
  const animRow = el('div', 'chip-row');
  for (const [key, val] of [
    ['none', t('animNone')],
    ['fade', t('animFade')],
    ['blink', t('animBlink')],
    ['slide', t('animSlide')],
  ] as [TextAnimation, string][]) {
    const chip = el('button', `chip${p.textAnimation === key ? ' active' : ''}`, val);
    chip.onclick = () => {
      p.textAnimation = key;
      render();
    };
    animRow.append(chip);
  }
  box.append(animRow);

  box.append(el('p', 'label', t('color')));
  const colors = el('div', 'colors');
  for (const c of PALETTE) {
    const dot = el('button', `color-dot${p.textColor === c ? ' active' : ''}`);
    dot.style.background = c;
    dot.onclick = () => {
      p.textColor = c;
      render();
    };
    colors.append(dot);
  }
  box.append(colors);

  box.append(el('p', 'label', t('templates')));
  const tplRow = el('div', 'chip-row');
  for (const tpl of TEMPLATES) {
    const chip = el('button', `chip${p.templateId === tpl.id ? ' active' : ''}`, templateLabel(tpl.id));
    chip.onclick = () => {
      p.templateId = tpl.id;
      p.textColor = tpl.color;
      render();
    };
    tplRow.append(chip);
  }
  box.append(tplRow);

  const exportBtn = el('button', 'btn primary', t('export'));
  exportBtn.onclick = () => void runExport();
  box.append(exportBtn);

  if (errorMsg) box.append(el('p', 'error', errorMsg));

  return box;
}

async function drawPreview(canvas: HTMLCanvasElement, p: GifProject): Promise<void> {
  const ctx = canvas.getContext('2d')!;
  if (p.sourceType === 'photos' && p.mediaUrls[0]) {
    const img = new Image();
    img.src = p.mediaUrls[0];
    await img.decode();
    const scale = p.width / img.naturalWidth;
    canvas.width = p.width;
    canvas.height = Math.max(1, Math.round(img.naturalHeight * scale));
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  } else if (p.mediaUrls[0]) {
    const video = document.createElement('video');
    video.src = p.mediaUrls[0];
    video.muted = true;
    await new Promise<void>((r) => {
      video.onloadeddata = () => r();
    });
    video.currentTime = p.trimStart;
    await new Promise<void>((r) => {
      video.onseeked = () => r();
    });
    const scale = p.width / video.videoWidth;
    canvas.width = p.width;
    canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  }

  if (p.text.trim()) {
    const tpl = TEMPLATES.find((x) => x.id === p.templateId) ?? TEMPLATES[0];
    ctx.font = `${tpl.fontWeight} ${tpl.fontSize}px Inter, system-ui, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    if (tpl.bg !== 'transparent') {
      ctx.fillStyle = tpl.bg;
      const m = ctx.measureText(p.text);
      const pad = 12;
      ctx.fillRect(
        canvas.width / 2 - m.width / 2 - pad,
        canvas.height * 0.85 - tpl.fontSize / 2 - pad,
        m.width + pad * 2,
        tpl.fontSize + pad * 2,
      );
    }
    ctx.fillStyle = p.textColor;
    ctx.fillText(p.text, canvas.width / 2, canvas.height * 0.85);
  }
}

function buildExport(): HTMLElement {
  const box = el('div', 'stack center');

  if (exportBlob) {
    box.append(el('h2', '', t('saved')));
    if (previewUrl) {
      const img = el('img', 'result-gif') as HTMLImageElement;
      img.src = previewUrl;
      box.append(img);
    }
    const dl = el('button', 'btn primary', t('download'));
    dl.onclick = () => downloadBlob(exportBlob!, `gifcraft-${Date.now()}.gif`);
    const share = el('button', 'btn ghost', t('share'));
    share.onclick = () => void shareBlob(exportBlob!, `gifcraft-${Date.now()}.gif`);
    const home = el('button', 'btn ghost', t('appTitle'));
    home.onclick = () => {
      view = 'home';
      exportBlob = null;
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = '';
      project = null;
      render();
    };
    box.append(dl, share, home);
  } else {
    box.append(el('div', 'spinner'));
    box.append(el('p', '', t('exporting')));
    const bar = el('div', 'progress');
    const fill = el('div', 'progress-fill');
    fill.style.width = `${Math.round(exportProgress * 100)}%`;
    bar.append(fill);
    box.append(bar);
    box.append(el('p', 'muted', exportPhase === 'frames' ? 'Frames…' : 'Encode…'));
  }

  return box;
}

async function runExport(): Promise<void> {
  if (!project) return;
  errorMsg = '';
  view = 'export';
  exportBlob = null;
  exportProgress = 0;
  render();

  try {
    pTouch(project);
    upsertDraftMeta(project);
    exportBlob = await buildGifBlob(project, (p) => {
      exportPhase = p.phase;
      exportProgress = p.value;
      render();
    });
    previewUrl = URL.createObjectURL(exportBlob);
    render();
  } catch (e) {
    errorMsg = e instanceof Error ? e.message : t('error');
    view = 'editor';
    render();
  }
}

function pTouch(p: GifProject): void {
  p.updatedAt = Date.now();
}

function toggleLang(): void {
  const next: Lang = getLang() === 'ru' ? 'en' : 'ru';
  setLang(next);
  render();
}

function el<K extends keyof HTMLElementTagNameMap>(
  tag: K,
  className?: string,
  text?: string,
): HTMLElementTagNameMap[K] {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text) node.textContent = text;
  return node;
}

function slider(
  label: string,
  value: number,
  min: number,
  max: number,
  step: number,
  onChange: (v: number) => void,
): HTMLElement {
  const wrap = el('div', 'field');
  const row = el('div', 'field-row');
  row.append(el('span', '', label), el('span', 'muted', String(value)));
  const input = el('input') as HTMLInputElement;
  input.type = 'range';
  input.min = String(min);
  input.max = String(max);
  input.step = String(step);
  input.value = String(value);
  input.oninput = () => onChange(Number(input.value));
  wrap.append(row, input);
  return wrap;
}

function labelRange(
  label: string,
  start: number,
  end: number,
  min: number,
  max: number,
  onChange: (s: number, e: number) => void,
): HTMLElement {
  const wrap = el('div', 'field');
  wrap.append(el('span', '', `${label}: ${start.toFixed(1)}–${end.toFixed(1)} ${t('seconds')}`));
  const startInput = el('input') as HTMLInputElement;
  startInput.type = 'range';
  startInput.min = String(min);
  startInput.max = String(max);
  startInput.step = '0.1';
  startInput.value = String(start);
  startInput.oninput = () => onChange(Number(startInput.value), end);
  const endInput = el('input') as HTMLInputElement;
  endInput.type = 'range';
  endInput.min = String(min);
  endInput.max = String(max);
  endInput.step = '0.1';
  endInput.value = String(end);
  endInput.oninput = () => onChange(start, Number(endInput.value));
  wrap.append(startInput, endInput);
  return wrap;
}
