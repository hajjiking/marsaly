import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'config/flavor.dart';
import 'core/theme/app_theme.dart';
import 'core/utils/local_notifications.dart';
import 'l10n/app_localizations.dart';
import 'shell/router.dart';
import 'core/providers/app_providers.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  LocalNotifications.init();
  runApp(const ProviderScope(child: MarsalyApp()));
}

class MarsalyApp extends ConsumerWidget {
  const MarsalyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    final locale = ref.watch(localeProvider);
    final themeMode = ref.watch(themeModeProvider);
    final brand = ref.watch(brandConfigProvider).valueOrNull;

    return MaterialApp.router(
      title: brand?.appName ?? Flavor.current.appName,
      debugShowCheckedModeBanner: false,
      routerConfig: router,
      locale: locale,
      supportedLocales: S.supportedLocales,
      localizationsDelegates: S.localizationsDelegates,
      theme: AppTheme.light(),
      darkTheme: AppTheme.dark(),
      themeMode: themeMode,
    );
  }
}
