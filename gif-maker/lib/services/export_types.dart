class ExportResult {
  ExportResult({required this.path, required this.mimeType});

  final String path;
  final String mimeType;
}

class ExportException implements Exception {
  ExportException(this.message);
  final String message;

  @override
  String toString() => message;
}
