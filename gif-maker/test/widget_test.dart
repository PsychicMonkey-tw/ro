import 'package:flutter_test/flutter_test.dart';
import 'package:gifcraft/models/gif_project.dart';

void main() {
  test('GifProject round-trip json', () {
    final project = GifProject(
      id: 'test-id',
      name: 'Test',
      sourceType: SourceType.video,
      mediaPaths: ['/tmp/video.mp4'],
      trimStartMs: 100,
      trimEndMs: 2500,
    );

    final restored = GifProject.fromJson(project.toJson());
    expect(restored.id, project.id);
    expect(restored.name, project.name);
    expect(restored.sourceType, SourceType.video);
    expect(restored.mediaPaths, project.mediaPaths);
    expect(restored.trimStartMs, 100);
    expect(restored.trimEndMs, 2500);
  });
}
