import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_en.dart';
import 'app_localizations_ru.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale) : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate = _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates = <LocalizationsDelegate<dynamic>>[
    delegate,
    GlobalMaterialLocalizations.delegate,
    GlobalCupertinoLocalizations.delegate,
    GlobalWidgetsLocalizations.delegate,
  ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('en'),
    Locale('ru')
  ];

  /// No description provided for @appTitle.
  ///
  /// In en, this message translates to:
  /// **'GifCraft'**
  String get appTitle;

  /// No description provided for @homeTagline.
  ///
  /// In en, this message translates to:
  /// **'Create GIFs from video, photos, or camera'**
  String get homeTagline;

  /// No description provided for @newGif.
  ///
  /// In en, this message translates to:
  /// **'New GIF'**
  String get newGif;

  /// No description provided for @drafts.
  ///
  /// In en, this message translates to:
  /// **'Drafts'**
  String get drafts;

  /// No description provided for @noDrafts.
  ///
  /// In en, this message translates to:
  /// **'No drafts yet'**
  String get noDrafts;

  /// No description provided for @deleteDraft.
  ///
  /// In en, this message translates to:
  /// **'Delete draft'**
  String get deleteDraft;

  /// No description provided for @deleteDraftConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete this draft permanently?'**
  String get deleteDraftConfirm;

  /// No description provided for @cancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get cancel;

  /// No description provided for @delete.
  ///
  /// In en, this message translates to:
  /// **'Delete'**
  String get delete;

  /// No description provided for @chooseSource.
  ///
  /// In en, this message translates to:
  /// **'Choose source'**
  String get chooseSource;

  /// No description provided for @sourceVideo.
  ///
  /// In en, this message translates to:
  /// **'Video'**
  String get sourceVideo;

  /// No description provided for @sourceVideoDesc.
  ///
  /// In en, this message translates to:
  /// **'Pick a clip from gallery'**
  String get sourceVideoDesc;

  /// No description provided for @sourcePhotos.
  ///
  /// In en, this message translates to:
  /// **'Photos'**
  String get sourcePhotos;

  /// No description provided for @sourcePhotosDesc.
  ///
  /// In en, this message translates to:
  /// **'Turn images into a slideshow GIF'**
  String get sourcePhotosDesc;

  /// No description provided for @sourceCamera.
  ///
  /// In en, this message translates to:
  /// **'Camera'**
  String get sourceCamera;

  /// No description provided for @sourceCameraDesc.
  ///
  /// In en, this message translates to:
  /// **'Record a short clip'**
  String get sourceCameraDesc;

  /// No description provided for @trimTitle.
  ///
  /// In en, this message translates to:
  /// **'Trim clip'**
  String get trimTitle;

  /// No description provided for @trimHint.
  ///
  /// In en, this message translates to:
  /// **'Drag handles to select the GIF segment'**
  String get trimHint;

  /// No description provided for @duration.
  ///
  /// In en, this message translates to:
  /// **'Duration'**
  String get duration;

  /// No description provided for @fps.
  ///
  /// In en, this message translates to:
  /// **'FPS'**
  String get fps;

  /// No description provided for @width.
  ///
  /// In en, this message translates to:
  /// **'Width'**
  String get width;

  /// No description provided for @continueButton.
  ///
  /// In en, this message translates to:
  /// **'Continue'**
  String get continueButton;

  /// No description provided for @editorTitle.
  ///
  /// In en, this message translates to:
  /// **'Editor'**
  String get editorTitle;

  /// No description provided for @addText.
  ///
  /// In en, this message translates to:
  /// **'Add text'**
  String get addText;

  /// No description provided for @textHint.
  ///
  /// In en, this message translates to:
  /// **'Enter text'**
  String get textHint;

  /// No description provided for @textAnimation.
  ///
  /// In en, this message translates to:
  /// **'Text animation'**
  String get textAnimation;

  /// No description provided for @animationNone.
  ///
  /// In en, this message translates to:
  /// **'None'**
  String get animationNone;

  /// No description provided for @animationFadeIn.
  ///
  /// In en, this message translates to:
  /// **'Fade in'**
  String get animationFadeIn;

  /// No description provided for @animationBlink.
  ///
  /// In en, this message translates to:
  /// **'Blink'**
  String get animationBlink;

  /// No description provided for @animationSlide.
  ///
  /// In en, this message translates to:
  /// **'Slide'**
  String get animationSlide;

  /// No description provided for @colorPalette.
  ///
  /// In en, this message translates to:
  /// **'Color'**
  String get colorPalette;

  /// No description provided for @templates.
  ///
  /// In en, this message translates to:
  /// **'Templates'**
  String get templates;

  /// No description provided for @templateClassic.
  ///
  /// In en, this message translates to:
  /// **'Classic'**
  String get templateClassic;

  /// No description provided for @templateBold.
  ///
  /// In en, this message translates to:
  /// **'Bold caption'**
  String get templateBold;

  /// No description provided for @templateMinimal.
  ///
  /// In en, this message translates to:
  /// **'Minimal'**
  String get templateMinimal;

  /// No description provided for @templateNeon.
  ///
  /// In en, this message translates to:
  /// **'Neon'**
  String get templateNeon;

  /// No description provided for @templateRetro.
  ///
  /// In en, this message translates to:
  /// **'Retro'**
  String get templateRetro;

  /// No description provided for @templateCinema.
  ///
  /// In en, this message translates to:
  /// **'Cinema'**
  String get templateCinema;

  /// No description provided for @layers.
  ///
  /// In en, this message translates to:
  /// **'Layers'**
  String get layers;

  /// No description provided for @exportTitle.
  ///
  /// In en, this message translates to:
  /// **'Export'**
  String get exportTitle;

  /// No description provided for @formatGif.
  ///
  /// In en, this message translates to:
  /// **'GIF'**
  String get formatGif;

  /// No description provided for @formatMp4.
  ///
  /// In en, this message translates to:
  /// **'MP4'**
  String get formatMp4;

  /// No description provided for @formatWebp.
  ///
  /// In en, this message translates to:
  /// **'WebP'**
  String get formatWebp;

  /// No description provided for @quality.
  ///
  /// In en, this message translates to:
  /// **'Quality'**
  String get quality;

  /// No description provided for @qualityLow.
  ///
  /// In en, this message translates to:
  /// **'Low'**
  String get qualityLow;

  /// No description provided for @qualityMedium.
  ///
  /// In en, this message translates to:
  /// **'Medium'**
  String get qualityMedium;

  /// No description provided for @qualityHigh.
  ///
  /// In en, this message translates to:
  /// **'High'**
  String get qualityHigh;

  /// No description provided for @saveToGallery.
  ///
  /// In en, this message translates to:
  /// **'Save to gallery'**
  String get saveToGallery;

  /// No description provided for @share.
  ///
  /// In en, this message translates to:
  /// **'Share'**
  String get share;

  /// No description provided for @exporting.
  ///
  /// In en, this message translates to:
  /// **'Exporting…'**
  String get exporting;

  /// No description provided for @exportSuccess.
  ///
  /// In en, this message translates to:
  /// **'Saved to gallery'**
  String get exportSuccess;

  /// No description provided for @exportFailed.
  ///
  /// In en, this message translates to:
  /// **'Export failed'**
  String get exportFailed;

  /// No description provided for @purchaseTitle.
  ///
  /// In en, this message translates to:
  /// **'Unlock GifCraft'**
  String get purchaseTitle;

  /// No description provided for @purchaseSubtitle.
  ///
  /// In en, this message translates to:
  /// **'One-time purchase — no subscription'**
  String get purchaseSubtitle;

  /// No description provided for @purchaseFeatureUnlimited.
  ///
  /// In en, this message translates to:
  /// **'Unlimited projects'**
  String get purchaseFeatureUnlimited;

  /// No description provided for @purchaseFeatureFormats.
  ///
  /// In en, this message translates to:
  /// **'GIF, MP4, and WebP export'**
  String get purchaseFeatureFormats;

  /// No description provided for @purchaseFeatureQuality.
  ///
  /// In en, this message translates to:
  /// **'Full quality, no watermark'**
  String get purchaseFeatureQuality;

  /// No description provided for @purchaseFeatureTemplates.
  ///
  /// In en, this message translates to:
  /// **'All templates and animations'**
  String get purchaseFeatureTemplates;

  /// No description provided for @purchaseButton.
  ///
  /// In en, this message translates to:
  /// **'Buy once'**
  String get purchaseButton;

  /// No description provided for @restorePurchase.
  ///
  /// In en, this message translates to:
  /// **'Restore purchase'**
  String get restorePurchase;

  /// No description provided for @demoMode.
  ///
  /// In en, this message translates to:
  /// **'Demo mode'**
  String get demoMode;

  /// No description provided for @demoLimitProjects.
  ///
  /// In en, this message translates to:
  /// **'Demo: 1 project limit'**
  String get demoLimitProjects;

  /// No description provided for @demoLimitExport.
  ///
  /// In en, this message translates to:
  /// **'Demo: GIF only, low quality, watermark'**
  String get demoLimitExport;

  /// No description provided for @upgrade.
  ///
  /// In en, this message translates to:
  /// **'Upgrade'**
  String get upgrade;

  /// No description provided for @unlocked.
  ///
  /// In en, this message translates to:
  /// **'Full version unlocked'**
  String get unlocked;

  /// No description provided for @pickPhotos.
  ///
  /// In en, this message translates to:
  /// **'Pick photos'**
  String get pickPhotos;

  /// No description provided for @pickVideo.
  ///
  /// In en, this message translates to:
  /// **'Pick video'**
  String get pickVideo;

  /// No description provided for @recordClip.
  ///
  /// In en, this message translates to:
  /// **'Record clip'**
  String get recordClip;

  /// No description provided for @seconds.
  ///
  /// In en, this message translates to:
  /// **'{count}s'**
  String seconds(String count);

  /// No description provided for @language.
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get language;

  /// No description provided for @languageSystem.
  ///
  /// In en, this message translates to:
  /// **'System'**
  String get languageSystem;

  /// No description provided for @languageEnglish.
  ///
  /// In en, this message translates to:
  /// **'English'**
  String get languageEnglish;

  /// No description provided for @languageRussian.
  ///
  /// In en, this message translates to:
  /// **'Russian'**
  String get languageRussian;

  /// No description provided for @errorCamera.
  ///
  /// In en, this message translates to:
  /// **'Camera unavailable'**
  String get errorCamera;

  /// No description provided for @errorPermission.
  ///
  /// In en, this message translates to:
  /// **'Permission denied'**
  String get errorPermission;

  /// No description provided for @watermark.
  ///
  /// In en, this message translates to:
  /// **'GifCraft'**
  String get watermark;
}

class _AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) => <String>['en', 'ru'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {


  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'en': return AppLocalizationsEn();
    case 'ru': return AppLocalizationsRu();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.'
  );
}
