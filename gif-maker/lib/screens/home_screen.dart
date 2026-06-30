import 'package:flutter/material.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:gifcraft/models/gif_project.dart';
import 'package:gifcraft/services/locale_service.dart';
import 'package:gifcraft/services/project_service.dart';
import 'package:gifcraft/services/purchase_service.dart';
import 'package:provider/provider.dart';

import 'purchase_screen.dart';
import 'source_picker_screen.dart';
import 'trim_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final projects = context.watch<ProjectService>().projects;
    final purchase = context.watch<PurchaseService>();

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.appTitle),
        actions: [
          IconButton(
            icon: const Icon(Icons.language),
            tooltip: l10n.language,
            onPressed: () => _showLanguageSheet(context),
          ),
          if (!purchase.isFullVersion)
            IconButton(
              icon: const Icon(Icons.lock_open),
              tooltip: l10n.upgrade,
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const PurchaseScreen()),
              ),
            ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            l10n.homeTagline,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          if (!purchase.isFullVersion)
            Card(
              child: ListTile(
                leading: const Icon(Icons.info_outline),
                title: Text(l10n.demoMode),
                subtitle: Text('${l10n.demoLimitProjects}\n${l10n.demoLimitExport}'),
                trailing: FilledButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const PurchaseScreen()),
                  ),
                  child: Text(l10n.upgrade),
                ),
              ),
            )
          else
            Card(
              child: ListTile(
                leading: const Icon(Icons.verified, color: Colors.green),
                title: Text(l10n.unlocked),
              ),
            ),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: () => _startNewGif(context),
            icon: const Icon(Icons.add),
            label: Text(l10n.newGif),
          ),
          const SizedBox(height: 24),
          Text(l10n.drafts, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 12),
          if (projects.isEmpty)
            Text(l10n.noDrafts, style: Theme.of(context).textTheme.bodyMedium)
          else
            ...projects.map((project) => _DraftTile(project: project)),
        ],
      ),
    );
  }

  Future<void> _startNewGif(BuildContext context) async {
    final purchase = context.read<PurchaseService>();
    final projectService = context.read<ProjectService>();

    if (!purchase.canCreateProject(projectService.projects.length)) {
      await Navigator.of(context).push(
        MaterialPageRoute(builder: (_) => const PurchaseScreen()),
      );
      return;
    }

    final source = await Navigator.of(context).push<SourceType>(
      MaterialPageRoute(builder: (_) => const SourcePickerScreen()),
    );
    if (source == null || !context.mounted) return;

    final paths = await SourcePickerScreen.pickMedia(context, source);
    if (paths == null || paths.isEmpty || !context.mounted) return;

    final projectServiceRef = context.read<ProjectService>();
    final copied = <String>[];
    for (final path in paths) {
      copied.add(await projectServiceRef.copyMediaToAppDir(path));
    }

    final project = await projectServiceRef.createProject(
      sourceType: source,
      mediaPaths: copied,
    );

    if (!context.mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => TrimScreen(project: project)),
    );
  }

  void _showLanguageSheet(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final localeService = context.read<LocaleService>();

    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              title: Text(l10n.languageSystem),
              trailing: localeService.localeOverride == null
                  ? const Icon(Icons.check)
                  : null,
              onTap: () {
                localeService.setLocale(null);
                Navigator.pop(ctx);
              },
            ),
            ListTile(
              title: Text(l10n.languageEnglish),
              trailing: localeService.localeOverride?.languageCode == 'en'
                  ? const Icon(Icons.check)
                  : null,
              onTap: () {
                localeService.setLocale(const Locale('en'));
                Navigator.pop(ctx);
              },
            ),
            ListTile(
              title: Text(l10n.languageRussian),
              trailing: localeService.localeOverride?.languageCode == 'ru'
                  ? const Icon(Icons.check)
                  : null,
              onTap: () {
                localeService.setLocale(const Locale('ru'));
                Navigator.pop(ctx);
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _DraftTile extends StatelessWidget {
  const _DraftTile({required this.project});

  final GifProject project;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: CircleAvatar(
          child: Icon(_iconForSource(project.sourceType)),
        ),
        title: Text(project.name),
        subtitle: Text(_sourceLabel(l10n, project.sourceType)),
        trailing: IconButton(
          icon: const Icon(Icons.delete_outline),
          onPressed: () async {
            final confirmed = await showDialog<bool>(
              context: context,
              builder: (ctx) => AlertDialog(
                title: Text(l10n.deleteDraft),
                content: Text(l10n.deleteDraftConfirm),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(ctx, false),
                    child: Text(l10n.cancel),
                  ),
                  FilledButton(
                    onPressed: () => Navigator.pop(ctx, true),
                    child: Text(l10n.delete),
                  ),
                ],
              ),
            );
            if (confirmed == true && context.mounted) {
              await context.read<ProjectService>().deleteProject(project.id);
            }
          },
        ),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => TrimScreen(project: project)),
        ),
      ),
    );
  }

  IconData _iconForSource(SourceType type) {
    switch (type) {
      case SourceType.video:
        return Icons.movie;
      case SourceType.photos:
        return Icons.photo_library;
      case SourceType.camera:
        return Icons.videocam;
    }
  }

  String _sourceLabel(AppLocalizations l10n, SourceType type) {
    switch (type) {
      case SourceType.video:
        return l10n.sourceVideo;
      case SourceType.photos:
        return l10n.sourcePhotos;
      case SourceType.camera:
        return l10n.sourceCamera;
    }
  }
}
