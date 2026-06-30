import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';

import 'video_preview_io.dart'
    if (dart.library.html) 'video_preview_web.dart';

Widget buildMediaImage(String path, {BoxFit fit = BoxFit.cover}) =>
    buildMediaImageImpl(path, fit: fit);

Future<VideoPlayerController> createVideoController(String path) =>
    createVideoControllerImpl(path);
