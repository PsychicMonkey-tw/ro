import 'dart:async';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:path_provider/path_provider.dart';

class CameraCaptureScreen extends StatefulWidget {
  const CameraCaptureScreen({super.key});

  @override
  State<CameraCaptureScreen> createState() => _CameraCaptureScreenState();
}

class _CameraCaptureScreenState extends State<CameraCaptureScreen> {
  CameraController? _controller;
  bool _recording = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initCamera();
  }

  Future<void> _initCamera() async {
    try {
      final cameras = await availableCameras();
      if (cameras.isEmpty) {
        setState(() => _error = 'no_camera');
        return;
      }
      final controller = CameraController(
        cameras.first,
        ResolutionPreset.medium,
        enableAudio: true,
      );
      await controller.initialize();
      if (!mounted) return;
      setState(() {
        _controller = controller;
      });
    } catch (_) {
      setState(() => _error = 'init_failed');
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _toggleRecord() async {
    final controller = _controller;
    if (controller == null || !controller.value.isInitialized) return;

    if (_recording) {
      final file = await controller.stopVideoRecording();
      if (!mounted) return;
      Navigator.pop(context, file.path);
      return;
    }

    await controller.startVideoRecording();
    setState(() => _recording = true);

    unawaited(Future<void>.delayed(const Duration(seconds: 8), () async {
      if (!mounted || !_recording) return;
      final file = await controller.stopVideoRecording();
      if (!mounted) return;
      setState(() => _recording = false);
      Navigator.pop(context, file.path);
    }));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final controller = _controller;

    if (_error != null) {
      return Scaffold(
        appBar: AppBar(title: Text(l10n.recordClip)),
        body: Center(child: Text(l10n.errorCamera)),
      );
    }

    if (controller == null || !controller.value.isInitialized) {
      return Scaffold(
        appBar: AppBar(title: Text(l10n.recordClip)),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: Text(l10n.recordClip),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      body: Stack(
        fit: StackFit.expand,
        children: [
          CameraPreview(controller),
          Align(
            alignment: Alignment.bottomCenter,
            child: Padding(
              padding: const EdgeInsets.only(bottom: 32),
              child: FloatingActionButton.large(
                backgroundColor: _recording ? Colors.red : Colors.white,
                onPressed: _toggleRecord,
                child: Icon(
                  _recording ? Icons.stop : Icons.fiber_manual_record,
                  color: _recording ? Colors.white : Colors.red,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

Future<String> tempVideoPath() async {
  final dir = await getTemporaryDirectory();
  return '${dir.path}/capture_${DateTime.now().millisecondsSinceEpoch}.mp4';
}
