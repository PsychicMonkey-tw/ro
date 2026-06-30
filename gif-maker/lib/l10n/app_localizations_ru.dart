import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Russian (`ru`).
class AppLocalizationsRu extends AppLocalizations {
  AppLocalizationsRu([String locale = 'ru']) : super(locale);

  @override
  String get appTitle => 'GifCraft';

  @override
  String get homeTagline => 'Создавайте GIF из видео, фото или камеры';

  @override
  String get newGif => 'Новый GIF';

  @override
  String get drafts => 'Черновики';

  @override
  String get noDrafts => 'Черновиков пока нет';

  @override
  String get deleteDraft => 'Удалить черновик';

  @override
  String get deleteDraftConfirm => 'Удалить этот черновик навсегда?';

  @override
  String get cancel => 'Отмена';

  @override
  String get delete => 'Удалить';

  @override
  String get chooseSource => 'Выберите источник';

  @override
  String get sourceVideo => 'Видео';

  @override
  String get sourceVideoDesc => 'Клип из галереи';

  @override
  String get sourcePhotos => 'Фото';

  @override
  String get sourcePhotosDesc => 'Слайдшоу из нескольких фото';

  @override
  String get sourceCamera => 'Камера';

  @override
  String get sourceCameraDesc => 'Записать короткий клип';

  @override
  String get trimTitle => 'Обрезка';

  @override
  String get trimHint => 'Перетащите маркеры, чтобы выбрать фрагмент';

  @override
  String get duration => 'Длительность';

  @override
  String get fps => 'Кадров/с';

  @override
  String get width => 'Ширина';

  @override
  String get continueButton => 'Далее';

  @override
  String get editorTitle => 'Редактор';

  @override
  String get addText => 'Добавить текст';

  @override
  String get textHint => 'Введите текст';

  @override
  String get textAnimation => 'Анимация текста';

  @override
  String get animationNone => 'Без анимации';

  @override
  String get animationFadeIn => 'Появление';

  @override
  String get animationBlink => 'Мигание';

  @override
  String get animationSlide => 'Движение';

  @override
  String get colorPalette => 'Цвет';

  @override
  String get templates => 'Шаблоны';

  @override
  String get templateClassic => 'Классика';

  @override
  String get templateBold => 'Жирная подпись';

  @override
  String get templateMinimal => 'Минимализм';

  @override
  String get templateNeon => 'Неон';

  @override
  String get templateRetro => 'Ретро';

  @override
  String get templateCinema => 'Кино';

  @override
  String get layers => 'Слои';

  @override
  String get exportTitle => 'Экспорт';

  @override
  String get formatGif => 'GIF';

  @override
  String get formatMp4 => 'MP4';

  @override
  String get formatWebp => 'WebP';

  @override
  String get quality => 'Качество';

  @override
  String get qualityLow => 'Низкое';

  @override
  String get qualityMedium => 'Среднее';

  @override
  String get qualityHigh => 'Высокое';

  @override
  String get saveToGallery => 'Сохранить в галерею';

  @override
  String get share => 'Поделиться';

  @override
  String get exporting => 'Экспорт…';

  @override
  String get exportSuccess => 'Сохранено в галерею';

  @override
  String get exportFailed => 'Ошибка экспорта';

  @override
  String get purchaseTitle => 'Разблокировать GifCraft';

  @override
  String get purchaseSubtitle => 'Разовая покупка — без подписки';

  @override
  String get purchaseFeatureUnlimited => 'Безлимитные проекты';

  @override
  String get purchaseFeatureFormats => 'Экспорт GIF, MP4 и WebP';

  @override
  String get purchaseFeatureQuality => 'Полное качество, без водяного знака';

  @override
  String get purchaseFeatureTemplates => 'Все шаблоны и анимации';

  @override
  String get purchaseButton => 'Купить';

  @override
  String get restorePurchase => 'Восстановить покупку';

  @override
  String get demoMode => 'Демо-режим';

  @override
  String get demoLimitProjects => 'Демо: лимит 1 проект';

  @override
  String get demoLimitExport => 'Демо: только GIF, низкое качество, водяной знак';

  @override
  String get upgrade => 'Купить полную версию';

  @override
  String get unlocked => 'Полная версия активна';

  @override
  String get pickPhotos => 'Выбрать фото';

  @override
  String get pickVideo => 'Выбрать видео';

  @override
  String get recordClip => 'Записать клип';

  @override
  String seconds(String count) {
    return '$count сек';
  }

  @override
  String get language => 'Язык';

  @override
  String get languageSystem => 'Системный';

  @override
  String get languageEnglish => 'English';

  @override
  String get languageRussian => 'Русский';

  @override
  String get errorCamera => 'Камера недоступна';

  @override
  String get errorPermission => 'Нет разрешения';

  @override
  String get watermark => 'GifCraft';
}
