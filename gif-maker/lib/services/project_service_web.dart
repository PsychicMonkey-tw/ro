class AppDirectory {
  const AppDirectory(this.path);
  final String path;
}

Future<AppDirectory> getAppDocumentsDirectory() async => const AppDirectory('.');

Future<AppDirectory> ensureMediaDirectory(String basePath) async =>
    AppDirectory(basePath);

Future<void> copyMediaFile(String sourcePath, String destPath) async {}

Future<void> deleteMediaFiles(List<String> paths) async {}
