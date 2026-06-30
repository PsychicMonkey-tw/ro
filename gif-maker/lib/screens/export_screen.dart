import 'package:flutter/material.dart';
import 'package:gal/gal.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:gifcraft/models/gif_project.dart';
import 'package:gifcraft/services/export_service.dart';
import 'package:gifcraft/services/purchase_service.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';

import 'purchase_screen.dart';

class ExportScreen extends StatefulWidget {
  const ExportScreen({super.key, required this.project});

  final GifProject project;

  @override
  State<ExportScreen> createState() => _ExportScreenState();
}

class _ExportScreenState extends State<ExportScreen> {
  ExportFormat _format = ExportFormat.gif;
  ExportQuality _quality = ExportQuality.medium;
  bool _busy = false;
  String? _lastExportPath;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final purchase = context.watch<PurchaseService>();

    return Scaffold(
      appBar: AppBar(title: Text(l10n.exportTitle)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(l10n.formatGif, style: Theme.of(context).textTheme.titleMedium),
          SegmentedButton<ExportFormat>(
            segments: [
              ButtonSegment(value: ExportFormat.gif, label: Text(l10n.formatGif)),
              ButtonSegment(
                value: ExportFormat.mp4,
                label: Text(l10n.formatMp4),
                enabled: purchase.canExportFormat(ExportFormatLike.mp4),
              ),
              ButtonSegment(
                value: ExportFormat.webp,
                label: Text(l10n.formatWebp),
                enabled: purchase.canExportFormat(ExportFormatLike.webp),
              ),
            ],
            selected: {_format},
            onSelectionChanged: (values) =>
                setState(() => _format = values.first),
          ),
          const SizedBox(height: 16),
          Text(l10n.quality),
          SegmentedButton<ExportQuality>(
            segments: [
              ButtonSegment(
                value: ExportQuality.low,
                label: Text(l10n.qualityLow),
              ),
              ButtonSegment(
                value: ExportQuality.medium,
                label: Text(l10n.qualityMedium),
                enabled: purchase.isFullVersion,
              ),
              ButtonSegment(
                value: ExportQuality.high,
                label: Text(l10n.qualityHigh),
                enabled: purchase.isFullVersion,
              ),
            ],
            selected: {_quality},
            onSelectionChanged: (values) =>
                setState(() => _quality = values.first),
          ),
          if (!purchase.isFullVersion) ...[
            const SizedBox(height: 12),
            Text(l10n.demoLimitExport),
            TextButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const PurchaseScreen()),
              ),
              child: Text(l10n.upgrade),
            ),
          ],
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _busy ? null : _exportAndSave,
            icon: _busy
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.download),
            label: Text(_busy ? l10n.exporting : l10n.saveToGallery),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _lastExportPath == null || _busy ? null : _share,
            icon: const Icon(Icons.share),
            label: Text(l10n.share),
          ),
        ],
      ),
    );
  }

  Future<void> _exportAndSave() async {
    final l10n = AppLocalizations.of(context)!;
    setState(() => _busy = true);
    try {
      final purchase = context.read<PurchaseService>();
      final service = ExportService(purchaseService: purchase);
      final result = await service.export(
        project: widget.project,
        format: _format,
        quality: _quality,
      );
      if (_format == ExportFormat.mp4) {
        await Gal.putVideo(result.path);
      } else {
        await Gal.putImage(result.path);
      }
      setState(() => _lastExportPath = result.path);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(l10n.exportSuccess)),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('${l10n.exportFailed}: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _share() async {
    final path = _lastExportPath;
    if (path == null) return;
    await Share.shareXFiles([XFile(path)]);
  }
}
