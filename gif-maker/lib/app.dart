import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:gifcraft/services/locale_service.dart';
import 'package:gifcraft/services/project_service.dart';
import 'package:gifcraft/services/purchase_service.dart';
import 'package:gifcraft/theme/app_theme.dart';
import 'package:provider/provider.dart';

import 'screens/home_screen.dart';

class GifCraftApp extends StatelessWidget {
  const GifCraftApp({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<LocaleService>(
      builder: (context, localeService, _) {
        return MaterialApp(
          title: 'GifCraft',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.light(),
          darkTheme: AppTheme.dark(),
          themeMode: ThemeMode.system,
          locale: localeService.localeOverride,
          localizationsDelegates: const [
            AppLocalizations.delegate,
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          supportedLocales: AppLocalizations.supportedLocales,
          home: const HomeScreen(),
        );
      },
    );
  }
}

class AppBootstrap extends StatefulWidget {
  const AppBootstrap({super.key});

  @override
  State<AppBootstrap> createState() => _AppBootstrapState();
}

class _AppBootstrapState extends State<AppBootstrap> {
  late final PurchaseService _purchaseService;
  late final ProjectService _projectService;
  late final LocaleService _localeService;
  bool _ready = false;

  @override
  void initState() {
    super.initState();
    _purchaseService = PurchaseService();
    _projectService = ProjectService();
    _localeService = LocaleService();
    _init();
  }

  Future<void> _init() async {
    try {
      await Future.wait([
        _purchaseService.init(),
        _projectService.init(),
        _localeService.init(),
      ]).timeout(const Duration(seconds: 10));
    } catch (_) {
      // Web or plugin failures should not block UI preview.
    }
    if (mounted) {
      setState(() => _ready = true);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_ready) {
      return MaterialApp(
        home: Scaffold(
          body: Center(
            child: CircularProgressIndicator(
              color: AppTheme.seed,
            ),
          ),
        ),
      );
    }

    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: _purchaseService),
        ChangeNotifierProvider.value(value: _projectService),
        ChangeNotifierProvider.value(value: _localeService),
      ],
      child: const GifCraftApp(),
    );
  }
}
