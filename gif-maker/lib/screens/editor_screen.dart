import 'dart:io';

import 'package:flutter/material.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:gifcraft/models/gif_project.dart';
import 'package:gifcraft/services/project_service.dart';
import 'package:gifcraft/services/purchase_service.dart';
import 'package:provider/provider.dart';
import 'package:uuid/uuid.dart';

import 'export_screen.dart';
import 'purchase_screen.dart';

class EditorScreen extends StatefulWidget {
  const EditorScreen({super.key, required this.project});

  final GifProject project;

  @override
  State<EditorScreen> createState() => _EditorScreenState();
}

class _EditorScreenState extends State<EditorScreen> {
  late GifProject _project;
  late TextLayer _activeLayer;
  late TextEditingController _textController;
  final _uuid = const Uuid();

  static const _palette = [
    Colors.white,
    Colors.black,
    Color(0xFF22D3EE),
    Color(0xFFF59E0B),
    Color(0xFFEF4444),
    Color(0xFF84CC16),
    Color(0xFFA855F7),
  ];

  @override
  void initState() {
    super.initState();
    _project = widget.project;
    if (_project.textLayers.isEmpty) {
      _activeLayer = TextLayer(id: _uuid.v4(), text: '');
      _project.textLayers = [_activeLayer];
    } else {
      _activeLayer = _project.textLayers.first;
    }
    _textController = TextEditingController(text: _activeLayer.text);
  }

  @override
  void dispose() {
    _textController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final purchase = context.watch<PurchaseService>();
    final template = GifTemplate.byId(_project.templateId) ?? GifTemplate.all.first;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.editorTitle),
        actions: [
          IconButton(
            icon: const Icon(Icons.save_outlined),
            onPressed: _save,
          ),
          IconButton(
            icon: const Icon(Icons.ios_share),
            onPressed: _goExport,
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          AspectRatio(
            aspectRatio: 1,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  _PreviewBackground(project: _project),
                  if (_activeLayer.text.isNotEmpty)
                    Align(
                      alignment: Alignment(
                        _activeLayer.x * 2 - 1,
                        _activeLayer.y * 2 - 1,
                      ),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                        decoration: BoxDecoration(
                          color: template.backgroundColor,
                          border: template.borderColor != null
                              ? Border.all(color: template.borderColor!)
                              : null,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          _activeLayer.text,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: _activeLayer.color,
                            fontSize: _activeLayer.fontSize,
                            fontWeight: template.id == 'bold'
                                ? FontWeight.bold
                                : FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  if (purchase.shouldWatermark)
                    Positioned(
                      top: 12,
                      right: 12,
                      child: Text(
                        l10n.watermark,
                        style: const TextStyle(
                          color: Colors.white70,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            decoration: InputDecoration(
              labelText: l10n.addText,
              hintText: l10n.textHint,
              border: const OutlineInputBorder(),
            ),
            controller: _textController,
            onChanged: (value) => setState(() => _activeLayer.text = value),
          ),
          const SizedBox(height: 12),
          Text(l10n.textAnimation),
          Wrap(
            spacing: 8,
            children: TextAnimation.values.map((animation) {
              final label = _animationLabel(l10n, animation);
              return ChoiceChip(
                label: Text(label),
                selected: _activeLayer.animation == animation,
                onSelected: purchase.isFullVersion || animation == TextAnimation.none
                    ? (_) => setState(() => _activeLayer.animation = animation)
                    : null,
              );
            }).toList(),
          ),
          const SizedBox(height: 12),
          Text(l10n.colorPalette),
          Wrap(
            spacing: 8,
            children: _palette.map((color) {
              final selected = _activeLayer.color.value == color.value;
              return GestureDetector(
                onTap: () => setState(() => _activeLayer.color = color),
                child: CircleAvatar(
                  backgroundColor: color,
                  radius: selected ? 18 : 16,
                  child: selected
                      ? Icon(
                          Icons.check,
                          color: color.computeLuminance() > 0.5
                              ? Colors.black
                              : Colors.white,
                          size: 18,
                        )
                      : null,
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 12),
          Text(l10n.templates),
          SizedBox(
            height: 44,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: GifTemplate.all.length,
              separatorBuilder: (_, __) => const SizedBox(width: 8),
              itemBuilder: (context, index) {
                final item = GifTemplate.all[index];
                final locked = !purchase.canUseTemplate(item.id);
                final label = _templateLabel(l10n, item.labelKey);
                return ChoiceChip(
                  label: Text(locked ? '$label 🔒' : label),
                  selected: _project.templateId == item.id,
                  onSelected: locked
                      ? null
                      : (_) => setState(() {
                            _project.templateId = item.id;
                            _activeLayer.fontSize = item.fontSize;
                            _activeLayer.color = item.textColor;
                          }),
                );
              },
            ),
          ),
          if (!purchase.isFullVersion) ...[
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const PurchaseScreen()),
              ),
              child: Text(l10n.upgrade),
            ),
          ],
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _goExport,
            child: Text(l10n.exportTitle),
          ),
        ],
      ),
    );
  }

  String _animationLabel(AppLocalizations l10n, TextAnimation animation) {
    switch (animation) {
      case TextAnimation.none:
        return l10n.animationNone;
      case TextAnimation.fadeIn:
        return l10n.animationFadeIn;
      case TextAnimation.blink:
        return l10n.animationBlink;
      case TextAnimation.slide:
        return l10n.animationSlide;
    }
  }

  String _templateLabel(AppLocalizations l10n, String key) {
    switch (key) {
      case 'templateClassic':
        return l10n.templateClassic;
      case 'templateBold':
        return l10n.templateBold;
      case 'templateMinimal':
        return l10n.templateMinimal;
      case 'templateNeon':
        return l10n.templateNeon;
      case 'templateRetro':
        return l10n.templateRetro;
      case 'templateCinema':
        return l10n.templateCinema;
      default:
        return key;
    }
  }

  Future<void> _save() async {
    await context.read<ProjectService>().saveProject(_project);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context)!.exportSuccess)),
      );
    }
  }

  Future<void> _goExport() async {
    await context.read<ProjectService>().saveProject(_project);
    if (!mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => ExportScreen(project: _project)),
    );
  }
}

class _PreviewBackground extends StatelessWidget {
  const _PreviewBackground({required this.project});

  final GifProject project;

  @override
  Widget build(BuildContext context) {
    if (project.sourceType == SourceType.photos &&
        project.mediaPaths.isNotEmpty) {
      return Image.file(
        File(project.mediaPaths.first),
        fit: BoxFit.cover,
      );
    }
    if (project.mediaPaths.isNotEmpty) {
      return Container(
        color: Colors.black,
        alignment: Alignment.center,
        child: const Icon(Icons.movie, color: Colors.white54, size: 64),
      );
    }
    return Container(color: Colors.black12);
  }
}
