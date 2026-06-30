import 'dart:io';

import 'package:flutter/material.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:gifcraft/models/gif_project.dart';
import 'package:image_picker/image_picker.dart';

import 'camera_capture_screen.dart';

class SourcePickerScreen extends StatelessWidget {
  const SourcePickerScreen({super.key});

  static Future<List<String>?> pickMedia(
    BuildContext context,
    SourceType source,
  ) async {
    final picker = ImagePicker();
    switch (source) {
      case SourceType.video:
        final file = await picker.pickVideo(source: ImageSource.gallery);
        if (file == null) return null;
        return [file.path];
      case SourceType.photos:
        final files = await picker.pickMultiImage();
        if (files.isEmpty) return null;
        return files.map((f) => f.path).toList();
      case SourceType.camera:
        final path = await Navigator.of(context).push<String>(
          MaterialPageRoute(builder: (_) => const CameraCaptureScreen()),
        );
        if (path == null) return null;
        return [path];
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.chooseSource)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          _SourceCard(
            icon: Icons.movie_outlined,
            title: l10n.sourceVideo,
            subtitle: l10n.sourceVideoDesc,
            onTap: () => Navigator.pop(context, SourceType.video),
          ),
          _SourceCard(
            icon: Icons.photo_library_outlined,
            title: l10n.sourcePhotos,
            subtitle: l10n.sourcePhotosDesc,
            onTap: () => Navigator.pop(context, SourceType.photos),
          ),
          _SourceCard(
            icon: Icons.videocam_outlined,
            title: l10n.sourceCamera,
            subtitle: l10n.sourceCameraDesc,
            onTap: () => Navigator.pop(context, SourceType.camera),
          ),
        ],
      ),
    );
  }
}

class _SourceCard extends StatelessWidget {
  const _SourceCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              CircleAvatar(
                radius: 28,
                child: Icon(icon, size: 28),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: Theme.of(context).textTheme.titleMedium),
                    const SizedBox(height: 4),
                    Text(subtitle),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }
}

Future<bool> mediaFileExists(String path) async => File(path).exists();
