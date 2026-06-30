import 'dart:io';

import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

class AppDirectory {
  const AppDirectory(this.path);
  final String path;
}

Future<AppDirectory> getAppDocumentsDirectory() async {
  final dir = await getApplicationDocumentsDirectory();
  return AppDirectory(dir.path);
}

Future<AppDirectory> ensureMediaDirectory(String basePath) async {
  final mediaDir = Directory(p.join(basePath, 'media'));
  if (!await mediaDir.exists()) {
    await mediaDir.create(recursive: true);
  }
  return AppDirectory(mediaDir.path);
}

Future<void> copyMediaFile(String sourcePath, String destPath) async {
  await File(sourcePath).copy(destPath);
}

Future<void> deleteMediaFiles(List<String> paths) async {
  for (final path in paths) {
    final file = File(path);
    if (await file.exists()) {
      await file.delete();
    }
  }
}
