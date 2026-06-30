import 'package:flutter/foundation.dart';

import '../models/gif_project.dart';
import 'export_types.dart';
import 'export_service_native.dart'
    if (dart.library.html) 'export_service_stub.dart';
import 'purchase_service.dart';

export 'export_types.dart';

class ExportService {
  ExportService({required this.purchaseService});

  final PurchaseService purchaseService;

  Future<ExportResult> export({
    required GifProject project,
    required ExportFormat format,
    required ExportQuality quality,
  }) async {
    if (kIsWeb) {
      throw ExportException(
        'Export works in the Android/iOS app. Web preview supports UI only.',
      );
    }

    return exportNative(
      purchaseService: purchaseService,
      project: project,
      format: format,
      quality: quality,
    );
  }
}

ExportFormatLike asExportFormatLike(ExportFormat format) {
  switch (format) {
    case ExportFormat.gif:
      return ExportFormatLike.gif;
    case ExportFormat.mp4:
      return ExportFormatLike.mp4;
    case ExportFormat.webp:
      return ExportFormatLike.webp;
  }
}

ExportQualityLike asExportQualityLike(ExportQuality quality) {
  switch (quality) {
    case ExportQuality.low:
      return ExportQualityLike.low;
    case ExportQuality.medium:
      return ExportQualityLike.medium;
    case ExportQuality.high:
      return ExportQualityLike.high;
  }
}
