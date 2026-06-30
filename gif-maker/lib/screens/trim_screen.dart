import 'dart:io';

import 'package:flutter/material.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:gifcraft/models/gif_project.dart';
import 'package:gifcraft/services/project_service.dart';
import 'package:provider/provider.dart';
import 'package:video_player/video_player.dart';

import 'editor_screen.dart';

class TrimScreen extends StatefulWidget {
  const TrimScreen({super.key, required this.project});

  final GifProject project;

  @override
  State<TrimScreen> createState() => _TrimScreenState();
}

class _TrimScreenState extends State<TrimScreen> {
  late GifProject _project;
  VideoPlayerController? _videoController;
  double _maxDurationMs = 10000;

  @override
  void initState() {
    super.initState();
    _project = widget.project;
    if (_project.sourceType != SourceType.photos &&
        _project.mediaPaths.isNotEmpty) {
      _initVideo();
    } else if (_project.sourceType == SourceType.photos) {
      _maxDurationMs =
          (_project.mediaPaths.length * 800).clamp(800, 12000).toDouble();
      _project.trimEndMs = _maxDurationMs;
    }
  }

  Future<void> _initVideo() async {
    final controller =
        VideoPlayerController.file(File(_project.mediaPaths.first));
    try {
      await controller.initialize();
      final durationMs =
          controller.value.duration.inMilliseconds.toDouble();
      if (!mounted) return;
      setState(() {
        _videoController = controller;
        _maxDurationMs = durationMs.clamp(1000, 60000);
        if (_project.trimEndMs > _maxDurationMs) {
          _project.trimEndMs = _maxDurationMs;
        }
      });
    } catch (_) {
      await controller.dispose();
    }
  }

  @override
  void dispose() {
    _videoController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final isPhotos = _project.sourceType == SourceType.photos;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.trimTitle)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          if (_videoController != null)
            AspectRatio(
              aspectRatio: _videoController!.value.aspectRatio,
              child: VideoPlayer(_videoController!),
            )
          else if (isPhotos)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  children: [
                    Icon(
                      Icons.photo_library,
                      size: 48,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(height: 8),
                    Text('${_project.mediaPaths.length} photos'),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 16),
          Text(l10n.trimHint),
          const SizedBox(height: 16),
          if (!isPhotos) ...[
            Text(l10n.duration),
            RangeSlider(
              values: RangeValues(_project.trimStartMs, _project.trimEndMs),
              min: 0,
              max: _maxDurationMs,
              onChanged: (values) {
                setState(() {
                  _project.trimStartMs = values.start;
                  _project.trimEndMs = values.end
                      .clamp(values.start + 500, _maxDurationMs);
                });
                _videoController?.seekTo(
                  Duration(milliseconds: values.start.round()),
                );
              },
            ),
            Text(l10n.seconds(
              ((_project.trimEndMs - _project.trimStartMs) / 1000)
                  .toStringAsFixed(1),
            )),
          ],
          const SizedBox(height: 16),
          Text(l10n.fps),
          Slider(
            value: _project.fps.toDouble(),
            min: 5,
            max: 20,
            divisions: 15,
            label: '${_project.fps}',
            onChanged: (v) => setState(() => _project.fps = v.round()),
          ),
          Text(l10n.width),
          Slider(
            value: _project.width.toDouble(),
            min: 240,
            max: 720,
            divisions: 6,
            label: '${_project.width}',
            onChanged: (v) => setState(() => _project.width = v.round()),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _continue,
            child: Text(l10n.continueButton),
          ),
        ],
      ),
    );
  }

  Future<void> _continue() async {
    await context.read<ProjectService>().saveProject(_project);
    if (!mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => EditorScreen(project: _project)),
    );
  }
}
