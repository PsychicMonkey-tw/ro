import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';

Widget buildMediaImageImpl(String path, {BoxFit fit = BoxFit.cover}) {
  return Image.network(path, fit: fit);
}

Future<VideoPlayerController> createVideoControllerImpl(String path) async {
  final controller = VideoPlayerController.networkUrl(Uri.parse(path));
  await controller.initialize();
  return controller;
}
