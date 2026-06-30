import { v4 as uuidv4 } from 'uuid';
import type { GifProject, SourceType } from './types';
import { getVideoDuration } from './gif-engine';

export function createProject(
  sourceType: SourceType,
  files: File[],
): Promise<GifProject> {
  const urls = files.map((f) => URL.createObjectURL(f));
  const base: GifProject = {
    id: uuidv4(),
    name: defaultName(sourceType),
    sourceType,
    mediaUrls: urls,
    mediaFiles: files,
    trimStart: 0,
    trimEnd: 3,
    fps: 10,
    width: 480,
    text: '',
    textColor: '#ffffff',
    textAnimation: 'none',
    templateId: 'classic',
    updatedAt: Date.now(),
  };

  if (sourceType === 'photos') {
    const duration = Math.min(files.length * 0.8, 8);
    base.trimEnd = duration;
    return Promise.resolve(base);
  }

  return getVideoDuration(urls[0]).then((duration) => {
    base.trimEnd = Math.min(duration, 5);
    return base;
  });
}

function defaultName(type: SourceType): string {
  switch (type) {
    case 'video':
      return 'Video GIF';
    case 'photos':
      return 'Photo GIF';
    case 'camera':
      return 'Camera GIF';
  }
}

export function revokeProject(project: GifProject): void {
  for (const url of project.mediaUrls) {
    URL.revokeObjectURL(url);
  }
}

export async function pickVideo(): Promise<File[]> {
  return pickFiles('video/*', false);
}

export async function pickPhotos(): Promise<File[]> {
  return pickFiles('image/*', true);
}

function pickFiles(accept: string, multiple: boolean): Promise<File[]> {
  return new Promise((resolve) => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = accept;
    input.multiple = multiple;
    input.onchange = () => {
      const files = input.files ? Array.from(input.files) : [];
      resolve(files);
    };
    input.click();
  });
}

export async function recordCamera(maxSec = 8): Promise<File> {
  const stream = await navigator.mediaDevices.getUserMedia({
    video: { facingMode: 'environment' },
    audio: false,
  });

  const mime =
    MediaRecorder.isTypeSupported('video/webm;codecs=vp9')
      ? 'video/webm;codecs=vp9'
      : 'video/webm';

  const recorder = new MediaRecorder(stream, { mimeType: mime });
  const chunks: Blob[] = [];

  recorder.ondataavailable = (e) => {
    if (e.data.size) chunks.push(e.data);
  };

  return new Promise((resolve, reject) => {
    recorder.onstop = () => {
      stream.getTracks().forEach((t) => t.stop());
      const blob = new Blob(chunks, { type: mime });
      resolve(new File([blob], `camera-${Date.now()}.webm`, { type: mime }));
    };
    recorder.onerror = () => {
      stream.getTracks().forEach((t) => t.stop());
      reject(new Error('Recording failed'));
    };

    recorder.start(200);
    setTimeout(() => {
      if (recorder.state === 'recording') recorder.stop();
    }, maxSec * 1000);
  });
}

export function downloadBlob(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

export async function shareBlob(blob: Blob, filename: string): Promise<boolean> {
  if (!navigator.share) return false;
  const file = new File([blob], filename, { type: blob.type });
  try {
    await navigator.share({ files: [file], title: 'GifCraft' });
    return true;
  } catch {
    return false;
  }
}
