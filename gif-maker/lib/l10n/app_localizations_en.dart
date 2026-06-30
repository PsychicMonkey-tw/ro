import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'GifCraft';

  @override
  String get homeTagline => 'Create GIFs from video, photos, or camera';

  @override
  String get newGif => 'New GIF';

  @override
  String get drafts => 'Drafts';

  @override
  String get noDrafts => 'No drafts yet';

  @override
  String get deleteDraft => 'Delete draft';

  @override
  String get deleteDraftConfirm => 'Delete this draft permanently?';

  @override
  String get cancel => 'Cancel';

  @override
  String get delete => 'Delete';

  @override
  String get chooseSource => 'Choose source';

  @override
  String get sourceVideo => 'Video';

  @override
  String get sourceVideoDesc => 'Pick a clip from gallery';

  @override
  String get sourcePhotos => 'Photos';

  @override
  String get sourcePhotosDesc => 'Turn images into a slideshow GIF';

  @override
  String get sourceCamera => 'Camera';

  @override
  String get sourceCameraDesc => 'Record a short clip';

  @override
  String get trimTitle => 'Trim clip';

  @override
  String get trimHint => 'Drag handles to select the GIF segment';

  @override
  String get duration => 'Duration';

  @override
  String get fps => 'FPS';

  @override
  String get width => 'Width';

  @override
  String get continueButton => 'Continue';

  @override
  String get editorTitle => 'Editor';

  @override
  String get addText => 'Add text';

  @override
  String get textHint => 'Enter text';

  @override
  String get textAnimation => 'Text animation';

  @override
  String get animationNone => 'None';

  @override
  String get animationFadeIn => 'Fade in';

  @override
  String get animationBlink => 'Blink';

  @override
  String get animationSlide => 'Slide';

  @override
  String get colorPalette => 'Color';

  @override
  String get templates => 'Templates';

  @override
  String get templateClassic => 'Classic';

  @override
  String get templateBold => 'Bold caption';

  @override
  String get templateMinimal => 'Minimal';

  @override
  String get templateNeon => 'Neon';

  @override
  String get templateRetro => 'Retro';

  @override
  String get templateCinema => 'Cinema';

  @override
  String get layers => 'Layers';

  @override
  String get exportTitle => 'Export';

  @override
  String get formatGif => 'GIF';

  @override
  String get formatMp4 => 'MP4';

  @override
  String get formatWebp => 'WebP';

  @override
  String get quality => 'Quality';

  @override
  String get qualityLow => 'Low';

  @override
  String get qualityMedium => 'Medium';

  @override
  String get qualityHigh => 'High';

  @override
  String get saveToGallery => 'Save to gallery';

  @override
  String get share => 'Share';

  @override
  String get exporting => 'Exporting…';

  @override
  String get exportSuccess => 'Saved to gallery';

  @override
  String get exportFailed => 'Export failed';

  @override
  String get purchaseTitle => 'Unlock GifCraft';

  @override
  String get purchaseSubtitle => 'One-time purchase — no subscription';

  @override
  String get purchaseFeatureUnlimited => 'Unlimited projects';

  @override
  String get purchaseFeatureFormats => 'GIF, MP4, and WebP export';

  @override
  String get purchaseFeatureQuality => 'Full quality, no watermark';

  @override
  String get purchaseFeatureTemplates => 'All templates and animations';

  @override
  String get purchaseButton => 'Buy once';

  @override
  String get restorePurchase => 'Restore purchase';

  @override
  String get demoMode => 'Demo mode';

  @override
  String get demoLimitProjects => 'Demo: 1 project limit';

  @override
  String get demoLimitExport => 'Demo: GIF only, low quality, watermark';

  @override
  String get upgrade => 'Upgrade';

  @override
  String get unlocked => 'Full version unlocked';

  @override
  String get pickPhotos => 'Pick photos';

  @override
  String get pickVideo => 'Pick video';

  @override
  String get recordClip => 'Record clip';

  @override
  String seconds(String count) {
    return '${count}s';
  }

  @override
  String get language => 'Language';

  @override
  String get languageSystem => 'System';

  @override
  String get languageEnglish => 'English';

  @override
  String get languageRussian => 'Russian';

  @override
  String get errorCamera => 'Camera unavailable';

  @override
  String get errorPermission => 'Permission denied';

  @override
  String get watermark => 'GifCraft';
}
