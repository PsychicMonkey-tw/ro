import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';

import '../models/gif_project.dart';

class ProjectService extends ChangeNotifier {
  ProjectService();

  static const _dbName = 'gifcraft.db';
  static const _table = 'projects';
  final _uuid = const Uuid();

  Database? _db;
  List<GifProject> _projects = [];

  List<GifProject> get projects => List.unmodifiable(_projects);

  Future<void> init() async {
    final dir = await getApplicationDocumentsDirectory();
    final dbPath = p.join(dir.path, _dbName);
    _db = await openDatabase(
      dbPath,
      version: 1,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE $_table (
            id TEXT PRIMARY KEY,
            payload TEXT NOT NULL,
            updated_at INTEGER NOT NULL
          )
        ''');
      },
    );
    await reload();
  }

  Future<void> reload() async {
    final db = _db;
    if (db == null) return;

    final rows = await db.query(_table, orderBy: 'updated_at DESC');
    _projects = rows
        .map((row) {
          final payload = jsonDecode(row['payload'] as String) as Map<String, dynamic>;
          return GifProject.fromJson(payload);
        })
        .toList();
    notifyListeners();
  }

  Future<GifProject> createProject({
    required SourceType sourceType,
    required List<String> mediaPaths,
    String? name,
  }) async {
    final project = GifProject(
      id: _uuid.v4(),
      name: name ?? _defaultName(sourceType),
      sourceType: sourceType,
      mediaPaths: List<String>.from(mediaPaths),
      trimEndMs: sourceType == SourceType.photos
          ? (mediaPaths.length * 800).clamp(800, 8000).toDouble()
          : 3000,
    );
    await saveProject(project);
    return project;
  }

  Future<void> saveProject(GifProject project) async {
    project.updatedAt = DateTime.now();
    final db = _db;
    if (db == null) return;

    await db.insert(
      _table,
      {
        'id': project.id,
        'payload': jsonEncode(project.toJson()),
        'updated_at': project.updatedAt.millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
    await reload();
  }

  Future<void> deleteProject(String id) async {
    final db = _db;
    if (db == null) return;

    final project = _projects.firstWhere((p) => p.id == id);
    for (final path in project.mediaPaths) {
      final file = File(path);
      if (await file.exists()) {
        await file.delete();
      }
    }

    await db.delete(_table, where: 'id = ?', whereArgs: [id]);
    await reload();
  }

  Future<String> copyMediaToAppDir(String sourcePath) async {
    final dir = await getApplicationDocumentsDirectory();
    final mediaDir = Directory(p.join(dir.path, 'media'));
    if (!await mediaDir.exists()) {
      await mediaDir.create(recursive: true);
    }
    final ext = p.extension(sourcePath);
    final dest = p.join(mediaDir.path, '${_uuid.v4()}$ext');
    await File(sourcePath).copy(dest);
    return dest;
  }

  String _defaultName(SourceType type) {
    switch (type) {
      case SourceType.video:
        return 'Video GIF';
      case SourceType.photos:
        return 'Photo GIF';
      case SourceType.camera:
        return 'Camera GIF';
    }
  }
}
