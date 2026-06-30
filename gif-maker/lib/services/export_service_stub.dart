import '../models/gif_project.dart';
import 'export_types.dart';
import 'purchase_service.dart';

Future<ExportResult> exportNative({
  required PurchaseService purchaseService,
  required GifProject project,
  required ExportFormat format,
  required ExportQuality quality,
}) {
  throw ExportException('Native export is unavailable on this platform.');
}
