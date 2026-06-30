import 'dart:io';

import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';

Widget buildMediaImageImpl(String path, {BoxFit fit = BoxFit.cover}) {
  if (_isNetworkPath(path)) {
    return Image.network(path, fit: fit);
  }
  return Image.file(File(path), fit: fit);
}

Future<VideoPlayerController> createVideoControllerImpl(String path) async {
  final controller = _isNetworkPath(path)
      ? VideoPlayerController.networkUrl(Uri.parse(path))
      : VideoPlayerController.file(File(path));
  await controller.initialize();
  return controller;
}

bool _isNetworkPath(String path) =>
    path.startsWith('blob:') ||
    path.startsWith('http://') ||
    path.startsWith('https://');
