import type { GifProject, SourceType } from './types';

const KEY = 'gifcraft_drafts_v2';

interface StoredDraft {
  id: string;
  name: string;
  sourceType: SourceType;
  trimStart: number;
  trimEnd: number;
  fps: number;
  width: number;
  text: string;
  textColor: string;
  textAnimation: GifProject['textAnimation'];
  templateId: string;
  updatedAt: number;
}

/** Files cannot persist in localStorage — drafts store settings only. */
export function loadDraftMeta(): StoredDraft[] {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return [];
    return JSON.parse(raw) as StoredDraft[];
  } catch {
    return [];
  }
}

export function saveDraftMeta(list: StoredDraft[]): void {
  localStorage.setItem(KEY, JSON.stringify(list));
}

export function upsertDraftMeta(project: GifProject): void {
  const list = loadDraftMeta().filter((d) => d.id !== project.id);
  list.unshift({
    id: project.id,
    name: project.name,
    sourceType: project.sourceType,
    trimStart: project.trimStart,
    trimEnd: project.trimEnd,
    fps: project.fps,
    width: project.width,
    text: project.text,
    textColor: project.textColor,
    textAnimation: project.textAnimation,
    templateId: project.templateId,
    updatedAt: project.updatedAt,
  });
  saveDraftMeta(list.slice(0, 20));
}

export function removeDraftMeta(id: string): void {
  saveDraftMeta(loadDraftMeta().filter((d) => d.id !== id));
}
