import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class LocaleService extends ChangeNotifier {
  LocaleService();

  static const _key = 'gifcraft_locale';
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  Locale? _override;

  Locale? get localeOverride => _override;

  Future<void> init() async {
    final code = await _storage.read(key: _key);
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
      await _storage.write(key: _key, value: 'system');
    } else {
      await _storage.write(key: _key, value: locale.languageCode);
    }
    notifyListeners();
  }
}
