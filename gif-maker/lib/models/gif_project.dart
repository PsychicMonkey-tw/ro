import 'dart:convert';
import 'dart:ui';

enum SourceType { video, photos, camera }

enum TextAnimation { none, fadeIn, blink, slide }

enum ExportFormat { gif, mp4, webp }

enum ExportQuality { low, medium, high }

class TextLayer {
  TextLayer({
    required this.id,
    required this.text,
    this.x = 0.5,
    this.y = 0.85,
    this.fontSize = 28,
    this.colorValue = 0xFFFFFFFF,
    this.animation = TextAnimation.none,
  });

  final String id;
  String text;
  double x;
  double y;
  double fontSize;
  int colorValue;
  TextAnimation animation;

  Color get color => Color(colorValue);

  set color(Color value) => colorValue = value.value;

  Map<String, dynamic> toJson() => {
        'id': id,
        'text': text,
        'x': x,
        'y': y,
        'fontSize': fontSize,
        'colorValue': colorValue,
        'animation': animation.name,
      };

  factory TextLayer.fromJson(Map<String, dynamic> json) => TextLayer(
        id: json['id'] as String,
        text: json['text'] as String? ?? '',
        x: (json['x'] as num?)?.toDouble() ?? 0.5,
        y: (json['y'] as num?)?.toDouble() ?? 0.85,
        fontSize: (json['fontSize'] as num?)?.toDouble() ?? 28,
        colorValue: json['colorValue'] as int? ?? 0xFFFFFFFF,
        animation: TextAnimation.values.firstWhere(
          (e) => e.name == json['animation'],
          orElse: () => TextAnimation.none,
        ),
      );
}

class GifProject {
  GifProject({
    required this.id,
    required this.name,
    required this.sourceType,
    required this.mediaPaths,
    this.trimStartMs = 0,
    this.trimEndMs = 3000,
    this.fps = 10,
    this.width = 480,
    this.textLayers = const [],
    this.templateId = 'classic',
    DateTime? updatedAt,
  }) : updatedAt = updatedAt ?? DateTime.now();

  final String id;
  String name;
  SourceType sourceType;
  List<String> mediaPaths;
  double trimStartMs;
  double trimEndMs;
  int fps;
  int width;
  List<TextLayer> textLayers;
  String? templateId;
  DateTime updatedAt;

  double get durationMs => (trimEndMs - trimStartMs).clamp(500, 15000);

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'sourceType': sourceType.name,
        'mediaPaths': mediaPaths,
        'trimStartMs': trimStartMs,
        'trimEndMs': trimEndMs,
        'fps': fps,
        'width': width,
        'textLayers': textLayers.map((l) => l.toJson()).toList(),
        'templateId': templateId,
        'updatedAt': updatedAt.toIso8601String(),
      };

  factory GifProject.fromJson(Map<String, dynamic> json) => GifProject(
        id: json['id'] as String,
        name: json['name'] as String? ?? 'GIF',
        sourceType: SourceType.values.firstWhere(
          (e) => e.name == json['sourceType'],
          orElse: () => SourceType.video,
        ),
        mediaPaths: (json['mediaPaths'] as List<dynamic>?)
                ?.map((e) => e as String)
                .toList() ??
            [],
        trimStartMs: (json['trimStartMs'] as num?)?.toDouble() ?? 0,
        trimEndMs: (json['trimEndMs'] as num?)?.toDouble() ?? 3000,
        fps: json['fps'] as int? ?? 10,
        width: json['width'] as int? ?? 480,
        textLayers: (json['textLayers'] as List<dynamic>?)
                ?.map((e) => TextLayer.fromJson(e as Map<String, dynamic>))
                .toList() ??
            [],
        templateId: json['templateId'] as String?,
        updatedAt: DateTime.tryParse(json['updatedAt'] as String? ?? '') ??
            DateTime.now(),
      );

  static String encodeList(List<GifProject> projects) =>
      jsonEncode(projects.map((p) => p.toJson()).toList());
}

class GifTemplate {
  const GifTemplate({
    required this.id,
    required this.labelKey,
    required this.backgroundColor,
    required this.textColor,
    required this.fontSize,
    this.borderColor,
    this.requiresFullVersion = false,
  });

  final String id;
  final String labelKey;
  final Color backgroundColor;
  final Color textColor;
  final double fontSize;
  final Color? borderColor;
  final bool requiresFullVersion;

  static const List<GifTemplate> all = [
    GifTemplate(
      id: 'classic',
      labelKey: 'templateClassic',
      backgroundColor: Color(0x00000000),
      textColor: Color(0xFFFFFFFF),
      fontSize: 28,
    ),
    GifTemplate(
      id: 'bold',
      labelKey: 'templateBold',
      backgroundColor: Color(0xCC000000),
      textColor: Color(0xFFFFFFFF),
      fontSize: 32,
    ),
    GifTemplate(
      id: 'minimal',
      labelKey: 'templateMinimal',
      backgroundColor: Color(0x00000000),
      textColor: Color(0xFFE2E8F0),
      fontSize: 22,
    ),
    GifTemplate(
      id: 'neon',
      labelKey: 'templateNeon',
      backgroundColor: Color(0x66000000),
      textColor: Color(0xFF22D3EE),
      fontSize: 30,
      requiresFullVersion: true,
    ),
    GifTemplate(
      id: 'retro',
      labelKey: 'templateRetro',
      backgroundColor: Color(0xCC7C2D12),
      textColor: Color(0xFFFFFBEB),
      fontSize: 26,
      requiresFullVersion: true,
    ),
    GifTemplate(
      id: 'cinema',
      labelKey: 'templateCinema',
      backgroundColor: Color(0xE61E293B),
      textColor: Color(0xFFF8FAFC),
      fontSize: 24,
      borderColor: Color(0xFFF59E0B),
      requiresFullVersion: true,
    ),
  ];

  static GifTemplate? byId(String? id) {
    if (id == null) return null;
    for (final t in all) {
      if (t.id == id) return t;
    }
    return null;
  }
}
