export type SourceType = 'video' | 'photos' | 'camera';
export type TextAnimation = 'none' | 'fade' | 'blink' | 'slide';
export type Lang = 'ru' | 'en';

export interface GifProject {
  id: string;
  name: string;
  sourceType: SourceType;
  /** Object URLs for preview; revoked on delete */
  mediaUrls: string[];
  /** Original files kept in memory for export */
  mediaFiles: File[];
  trimStart: number;
  trimEnd: number;
  fps: number;
  width: number;
  text: string;
  textColor: string;
  textAnimation: TextAnimation;
  templateId: string;
  updatedAt: number;
}

export interface TemplateDef {
  id: string;
  bg: string;
  color: string;
  fontSize: number;
  fontWeight: string;
}

export const TEMPLATES: TemplateDef[] = [
  { id: 'classic', bg: 'transparent', color: '#ffffff', fontSize: 28, fontWeight: '600' },
  { id: 'bold', bg: 'rgba(0,0,0,0.75)', color: '#ffffff', fontSize: 32, fontWeight: '700' },
  { id: 'minimal', bg: 'transparent', color: '#e2e8f0', fontSize: 22, fontWeight: '500' },
  { id: 'neon', bg: 'rgba(0,0,0,0.4)', color: '#22d3ee', fontSize: 30, fontWeight: '600' },
  { id: 'retro', bg: 'rgba(124,45,18,0.85)', color: '#fffbeb', fontSize: 26, fontWeight: '600' },
  { id: 'cinema', bg: 'rgba(30,41,59,0.9)', color: '#f8fafc', fontSize: 24, fontWeight: '600' },
];
