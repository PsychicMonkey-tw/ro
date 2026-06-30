import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:path/path.dart' as p;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';

import '../models/gif_project.dart';
import 'project_service_io.dart'
    if (dart.library.html) 'project_service_web.dart';

class ProjectService extends ChangeNotifier {
  ProjectService();

  static const _dbName = 'gifcraft.db';
  static const _table = 'projects';
  static const _webStorageKey = 'gifcraft_projects';
  final _uuid = const Uuid();

  Database? _db;
  List<GifProject> _projects = [];
  SharedPreferences? _prefs;

  List<GifProject> get projects => List.unmodifiable(_projects);

  Future<void> init() async {
    if (kIsWeb) {
      _prefs = await SharedPreferences.getInstance();
      await reload();
      return;
    }

    final dir = await getAppDocumentsDirectory();
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
    if (kIsWeb) {
      final raw = _prefs?.getString(_webStorageKey);
      if (raw == null || raw.isEmpty) {
        _projects = [];
      } else {
        final list = jsonDecode(raw) as List<dynamic>;
        _projects = list
            .map((item) => GifProject.fromJson(item as Map<String, dynamic>))
            .toList();
      }
      notifyListeners();
      return;
    }

    final db = _db;
    if (db == null) return;

    final rows = await db.query(_table, orderBy: 'updated_at DESC');
    _projects = rows
        .map((row) {
          final payload =
              jsonDecode(row['payload'] as String) as Map<String, dynamic>;
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

    if (kIsWeb) {
      final index = _projects.indexWhere((item) => item.id == project.id);
      if (index >= 0) {
        _projects[index] = project;
      } else {
        _projects.insert(0, project);
      }
      await _prefs?.setString(
        _webStorageKey,
        jsonEncode(_projects.map((item) => item.toJson()).toList()),
      );
      notifyListeners();
      return;
    }

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
    if (kIsWeb) {
      _projects.removeWhere((item) => item.id == id);
      await _prefs?.setString(
        _webStorageKey,
        jsonEncode(_projects.map((item) => item.toJson()).toList()),
      );
      notifyListeners();
      return;
    }

    final db = _db;
    if (db == null) return;

    final project = _projects.firstWhere((item) => item.id == id);
    await deleteMediaFiles(project.mediaPaths);

    await db.delete(_table, where: 'id = ?', whereArgs: [id]);
    await reload();
  }

  Future<String> copyMediaToAppDir(String sourcePath) async {
    if (kIsWeb) {
      return sourcePath;
    }

    final dir = await getAppDocumentsDirectory();
    final mediaDir = await ensureMediaDirectory(dir.path);
    final ext = p.extension(sourcePath);
    final dest = p.join(mediaDir.path, '${_uuid.v4()}$ext');
    await copyMediaFile(sourcePath, dest);
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
