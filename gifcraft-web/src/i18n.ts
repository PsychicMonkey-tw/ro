import type { Lang } from './types';

const messages = {
  ru: {
    appTitle: 'GifCraft',
    tagline: 'Создавайте GIF из видео, фото или камеры — прямо в браузере',
    newGif: 'Новый GIF',
    drafts: 'Черновики',
    noDrafts: 'Черновиков пока нет',
    delete: 'Удалить',
    cancel: 'Отмена',
    chooseSource: 'Выберите источник',
    sourceVideo: 'Видео',
    sourceVideoDesc: 'Загрузить клип с устройства',
    sourcePhotos: 'Фото',
    sourcePhotosDesc: 'Слайдшоу из нескольких изображений',
    sourceCamera: 'Камера',
    sourceCameraDesc: 'Записать короткий клип',
    trim: 'Обрезка',
    duration: 'Длительность',
    fps: 'Кадров в секунду',
    width: 'Ширина',
    continue: 'Далее',
    editor: 'Редактор',
    text: 'Текст на GIF',
    textPlaceholder: 'Введите подпись',
    animation: 'Анимация текста',
    animNone: 'Без анимации',
    animFade: 'Появление',
    animBlink: 'Мигание',
    animSlide: 'Движение',
    color: 'Цвет текста',
    templates: 'Шаблоны',
    export: 'Экспорт GIF',
    exporting: 'Создаём GIF…',
    download: 'Скачать GIF',
    share: 'Поделиться',
    saved: 'GIF готов!',
    error: 'Ошибка',
    language: 'Язык',
    back: 'Назад',
    record: 'Запись',
    stop: 'Стоп',
    cameraHint: 'Нажмите для записи (до 8 сек)',
    seconds: 'сек',
    tplClassic: 'Классика',
    tplBold: 'Жирная',
    tplMinimal: 'Минимализм',
    tplNeon: 'Неон',
    tplRetro: 'Ретро',
    tplCinema: 'Кино',
  },
  en: {
    appTitle: 'GifCraft',
    tagline: 'Create GIFs from video, photos, or camera — in your browser',
    newGif: 'New GIF',
    drafts: 'Drafts',
    noDrafts: 'No drafts yet',
    delete: 'Delete',
    cancel: 'Cancel',
    chooseSource: 'Choose source',
    sourceVideo: 'Video',
    sourceVideoDesc: 'Upload a clip from your device',
    sourcePhotos: 'Photos',
    sourcePhotosDesc: 'Slideshow from multiple images',
    sourceCamera: 'Camera',
    sourceCameraDesc: 'Record a short clip',
    trim: 'Trim',
    duration: 'Duration',
    fps: 'Frames per second',
    width: 'Width',
    continue: 'Continue',
    editor: 'Editor',
    text: 'Caption',
    textPlaceholder: 'Enter text',
    animation: 'Text animation',
    animNone: 'None',
    animFade: 'Fade in',
    animBlink: 'Blink',
    animSlide: 'Slide',
    color: 'Text color',
    templates: 'Templates',
    export: 'Export GIF',
    exporting: 'Building GIF…',
    download: 'Download GIF',
    share: 'Share',
    saved: 'GIF is ready!',
    error: 'Error',
    language: 'Language',
    back: 'Back',
    record: 'Record',
    stop: 'Stop',
    cameraHint: 'Tap to record (up to 8 sec)',
    seconds: 'sec',
    tplClassic: 'Classic',
    tplBold: 'Bold',
    tplMinimal: 'Minimal',
    tplNeon: 'Neon',
    tplRetro: 'Retro',
    tplCinema: 'Cinema',
  },
} as const;

export type MessageKey = keyof typeof messages.ru;

let currentLang: Lang = detectLang();

function detectLang(): Lang {
  const stored = localStorage.getItem('gifcraft_lang') as Lang | null;
  if (stored === 'ru' || stored === 'en') return stored;
  return navigator.language.startsWith('ru') ? 'ru' : 'en';
}

export function getLang(): Lang {
  return currentLang;
}

export function setLang(lang: Lang): void {
  currentLang = lang;
  localStorage.setItem('gifcraft_lang', lang);
  document.documentElement.lang = lang;
}

export function t(key: MessageKey): string {
  return messages[currentLang][key];
}

export function templateLabel(id: string): string {
  const map: Record<string, MessageKey> = {
    classic: 'tplClassic',
    bold: 'tplBold',
    minimal: 'tplMinimal',
    neon: 'tplNeon',
    retro: 'tplRetro',
    cinema: 'tplCinema',
  };
  return t(map[id] ?? 'tplClassic');
}

setLang(currentLang);
