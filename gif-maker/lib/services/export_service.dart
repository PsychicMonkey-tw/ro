import 'dart:io';
import 'dart:ui';

import 'package:ffmpeg_kit_flutter_new/ffmpeg_kit.dart';
import 'package:ffmpeg_kit_flutter_new/return_code.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:uuid/uuid.dart';

import '../models/gif_project.dart';
import 'purchase_service.dart';

class ExportResult {
  ExportResult({required this.path, required this.mimeType});

  final String path;
  final String mimeType;
}

class ExportService {
  ExportService({required this.purchaseService});

  final PurchaseService purchaseService;
  final _uuid = const Uuid();

  Future<ExportResult> export({
    required GifProject project,
    required ExportFormat format,
    required ExportQuality quality,
  }) async {
    if (!purchaseService.canExportFormat(_asFormatLike(format))) {
      throw ExportException('Format locked in demo mode');
    }

    final effectiveQuality =
        purchaseService.maxQuality(_asQualityLike(quality));
    final dir = await getTemporaryDirectory();
    final ext = _extension(format);
    final outputPath = p.join(dir.path, '${_uuid.v4()}.$ext');

    final command = _buildCommand(
      project: project,
      format: format,
      quality: effectiveQuality,
      outputPath: outputPath,
      watermark: purchaseService.shouldWatermark,
    );

    final session = await FFmpegKit.execute(command);
    final returnCode = await session.getReturnCode();
    if (!ReturnCode.isSuccess(returnCode)) {
      final logs = await session.getAllLogsAsString();
      throw ExportException(logs ?? 'FFmpeg failed');
    }

    if (!File(outputPath).existsSync()) {
      throw ExportException('Output file missing');
    }

    return ExportResult(path: outputPath, mimeType: _mimeType(format));
  }

  String _buildCommand({
    required GifProject project,
    required ExportFormat format,
    required ExportQualityLike quality,
    required String outputPath,
    required bool watermark,
  }) {
    if (project.sourceType == SourceType.photos) {
      return _photosCommand(project, format, quality, outputPath, watermark);
    }
    return _videoCommand(project, format, quality, outputPath, watermark);
  }

  String _videoCommand(
    GifProject project,
    ExportFormat format,
    ExportQualityLike quality,
    String outputPath,
    bool watermark,
  ) {
    final startSec = (project.trimStartMs / 1000).toStringAsFixed(2);
    final durationSec = (project.durationMs / 1000).toStringAsFixed(2);
    final input = _escapePath(project.mediaPaths.first);
    final vf = _videoFilters(project, quality, watermark);

    switch (format) {
      case ExportFormat.gif:
        return '-y -ss $startSec -t $durationSec -i $input -vf "$vf,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" -loop 0 ${_escapePath(outputPath)}';
      case ExportFormat.mp4:
        return '-y -ss $startSec -t $durationSec -i $input -vf "$vf" -an -c:v libx264 -pix_fmt yuv420p -movflags +faststart ${_escapePath(outputPath)}';
      case ExportFormat.webp:
        return '-y -ss $startSec -t $durationSec -i $input -vf "$vf" -loop 0 -c:v libwebp -quality 80 ${_escapePath(outputPath)}';
    }
  }

  String _photosCommand(
    GifProject project,
    ExportFormat format,
    ExportQualityLike quality,
    String outputPath,
    bool watermark,
  ) {
    final slideSec = (project.durationMs / project.mediaPaths.length / 1000)
        .clamp(0.3, 2.0)
        .toStringAsFixed(2);
    final inputs =
        project.mediaPaths.map((path) => '-loop 1 -t $slideSec -i ${_escapePath(path)}').join(' ');
    final count = project.mediaPaths.length;
    final vf = _videoFilters(project, quality, watermark);
    final filter =
        '"${List.generate(count, (i) => '[$i:v]scale=${_width(quality)}:-1:flags=lanczos,setpts=PTS-STARTPTS[v$i]').join(';')};${List.generate(count, (i) => '[v$i]').join('')}concat=n=$count:v=1:a=0,fps=${project.fps},$vf[outv]"';

    final base = '-y $inputs -filter_complex $filter -map "[outv]"';

    switch (format) {
      case ExportFormat.gif:
        return '$base -split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse -loop 0 ${_escapePath(outputPath)}';
      case ExportFormat.mp4:
        return '$base -c:v libx264 -pix_fmt yuv420p -movflags +faststart ${_escapePath(outputPath)}';
      case ExportFormat.webp:
        return '$base -loop 0 -c:v libwebp -quality 80 ${_escapePath(outputPath)}';
    }
  }

  String _videoFilters(GifProject project, ExportQualityLike quality, bool watermark) {
    final filters = <String>[
      'fps=${project.fps.clamp(5, 24)}',
      'scale=${_width(quality)}:-1:flags=lanczos',
    ];

    final textFilter = _textOverlayFilter(project);
    if (textFilter != null) filters.add(textFilter);

    if (watermark) {
      filters.add(
        "drawtext=text='GifCraft':x=w-tw-16:y=16:fontsize=18:fontcolor=white@0.7",
      );
    }

    return filters.join(',');
  }

  String? _textOverlayFilter(GifProject project) {
    if (project.textLayers.isEmpty) return null;
    final layer = project.textLayers.first;
    if (layer.text.trim().isEmpty) return null;

    final escaped = layer.text.replaceAll("'", r"'\''").replaceAll(':', r'\:');
    final color = _hexColor(layer.color);
    final yExpr = 'h*${layer.y.toStringAsFixed(3)}';
    final xExpr = 'w*${layer.x.toStringAsFixed(3)}';

    var alpha = '1';
    switch (layer.animation) {
      case TextAnimation.none:
        break;
      case TextAnimation.fadeIn:
        alpha = 'if(lt(t\\,0.6)\\,t/0.6\\,1)';
      case TextAnimation.blink:
        alpha = 'if(eq(mod(floor(t*2)\\,2)\\,0)\\,1\\,0.35)';
      case TextAnimation.slide:
        return "drawtext=text='$escaped':x='min($xExpr\\,w*${layer.x.toStringAsFixed(3)}+t*40)':y=$yExpr:fontsize=${layer.fontSize.round()}:fontcolor=$color@$alpha";
    }

    return "drawtext=text='$escaped':x=$xExpr:y=$yExpr:fontsize=${layer.fontSize.round()}:fontcolor=$color@$alpha";
  }

  String _escapePath(String path) => "'${path.replaceAll("'", "'\\''")}'";

  String _hexColor(Color color) {
    final value = color.value.toRadixString(16).padLeft(8, '0').substring(2);
    return '0x$value';
  }

  int _width(ExportQualityLike quality) {
    switch (quality) {
      case ExportQualityLike.low:
        return 360;
      case ExportQualityLike.medium:
        return 480;
      case ExportQualityLike.high:
        return 640;
    }
  }

  String _extension(ExportFormat format) {
    switch (format) {
      case ExportFormat.gif:
        return 'gif';
      case ExportFormat.mp4:
        return 'mp4';
      case ExportFormat.webp:
        return 'webp';
    }
  }

  String _mimeType(ExportFormat format) {
    switch (format) {
      case ExportFormat.gif:
        return 'image/gif';
      case ExportFormat.mp4:
        return 'video/mp4';
      case ExportFormat.webp:
        return 'image/webp';
    }
  }

  ExportFormatLike _asFormatLike(ExportFormat format) {
    switch (format) {
      case ExportFormat.gif:
        return ExportFormatLike.gif;
      case ExportFormat.mp4:
        return ExportFormatLike.mp4;
      case ExportFormat.webp:
        return ExportFormatLike.webp;
    }
  }

  ExportQualityLike _asQualityLike(ExportQuality quality) {
    switch (quality) {
      case ExportQuality.low:
        return ExportQualityLike.low;
      case ExportQuality.medium:
        return ExportQualityLike.medium;
      case ExportQuality.high:
        return ExportQualityLike.high;
    }
  }
}

class ExportException implements Exception {
  ExportException(this.message);
  final String message;

  @override
  String toString() => message;
}
