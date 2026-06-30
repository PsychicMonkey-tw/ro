import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LocaleService extends ChangeNotifier {
  LocaleService();

  static const _key = 'gifcraft_locale';
  SharedPreferences? _prefs;

  Locale? _override;

  Locale? get localeOverride => _override;

  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
    final code = _prefs?.getString(_key);
    if (code == null || code == 'system') {
      _override = null;
    } else {
      _override = Locale(code);
    }
    notifyListeners();
  }

  Future<void> setLocale(Locale? locale) async {
    _override = locale;
    if (locale == null) {
      await _prefs?.setString(_key, 'system');
    } else {
      await _prefs?.setString(_key, locale.languageCode);
    }
    notifyListeners();
  }
}
