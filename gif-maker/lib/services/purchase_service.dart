import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:in_app_purchase/in_app_purchase.dart';
import 'package:shared_preferences/shared_preferences.dart';

class PurchaseService extends ChangeNotifier {
  PurchaseService();

  static const _storageKey = 'gifcraft_full_unlocked';
  static const androidProductId = 'gifcraft_full_unlock';
  static const iosProductId = 'gifcraft_full_unlock';

  SharedPreferences? _prefs;
  final InAppPurchase _iap = InAppPurchase.instance;

  bool _isFullVersion = false;
  bool _isLoading = false;
  String? _error;
  ProductDetails? _product;

  bool get isFullVersion => _isFullVersion;
  bool get isLoading => _isLoading;
  String? get error => _error;
  ProductDetails? get product => _product;

  String get productId {
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      return iosProductId;
    }
    return androidProductId;
  }

  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
    _isFullVersion = _prefs?.getBool(_storageKey) ?? false;

    if (kIsWeb) {
      _isFullVersion = true;
      notifyListeners();
      return;
    }

    notifyListeners();

    if (!await _iap.isAvailable()) {
      return;
    }

    final response = await _iap.queryProductDetails({productId});
    if (response.productDetails.isNotEmpty) {
      _product = response.productDetails.first;
      notifyListeners();
    }

    _iap.purchaseStream.listen(_onPurchaseUpdate);
  }

  Future<void> buy() async {
    _error = null;
    if (_isFullVersion) return;

    if (kIsWeb || _product == null) {
      await _demoUnlockForDevelopment();
      return;
    }

    _isLoading = true;
    notifyListeners();

    final purchaseParam = PurchaseParam(productDetails: _product!);
    await _iap.buyNonConsumable(purchaseParam: purchaseParam);
  }

  Future<void> restore() async {
    if (kIsWeb) return;

    _isLoading = true;
    _error = null;
    notifyListeners();
    await _iap.restorePurchases();
    _isLoading = false;
    notifyListeners();
  }

  Future<void> _demoUnlockForDevelopment() async {
    if (kReleaseMode && _product == null && !kIsWeb) {
      _error = 'Store product not configured';
      notifyListeners();
      return;
    }
    await _setUnlocked(true);
  }

  Future<void> _onPurchaseUpdate(List<PurchaseDetails> purchases) async {
    for (final purchase in purchases) {
      if (purchase.productID != productId) continue;

      if (purchase.status == PurchaseStatus.purchased ||
          purchase.status == PurchaseStatus.restored) {
        await _setUnlocked(true);
      } else if (purchase.status == PurchaseStatus.error) {
        _error = purchase.error?.message;
      }

      if (purchase.pendingCompletePurchase) {
        await _iap.completePurchase(purchase);
      }
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> _setUnlocked(bool value) async {
    _isFullVersion = value;
    await _prefs?.setBool(_storageKey, value);
    notifyListeners();
  }

  bool canCreateProject(int currentDraftCount) {
    if (_isFullVersion) return true;
    return currentDraftCount < 1;
  }

  bool canUseTemplate(String templateId) {
    if (_isFullVersion) return true;
    return templateId == 'classic' ||
        templateId == 'bold' ||
        templateId == 'minimal';
  }

  bool canExportFormat(ExportFormatLike format) {
    if (_isFullVersion) return true;
    return format == ExportFormatLike.gif;
  }

  bool get shouldWatermark => !_isFullVersion;

  ExportQualityLike maxQuality(ExportQualityLike requested) {
    if (_isFullVersion) return requested;
    return ExportQualityLike.low;
  }
}

enum ExportFormatLike { gif, mp4, webp }

enum ExportQualityLike { low, medium, high }

String purchaseDebugInfo(PurchaseService service) => jsonEncode({
      'full': service.isFullVersion,
      'product': service.product?.price,
    });
