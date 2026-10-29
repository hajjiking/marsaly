import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../api/api_client.dart';
import '../api/token_store.dart';
import '../models/dto.dart';
import 'auth_repository.dart';
import 'catalog_repository.dart';
import 'order_repository.dart';
import 'panel_repositories.dart';
import 'settings_provider.dart';

final tokenStoreProvider = Provider<TokenStore>((ref) => TokenStore());

final settingsProvider = Provider<SettingsProvider>((ref) => SettingsProvider());

final apiClientProvider = Provider<ApiClient>((ref) {
  final client = ApiClient(
    tokenStore: ref.watch(tokenStoreProvider),
    settings: ref.watch(settingsProvider),
  );
  return client;
});

final authRepositoryProvider = Provider<AuthRepository>(
    (ref) => AuthRepository(api: ref.watch(apiClientProvider), tokenStore: ref.watch(tokenStoreProvider)));

final catalogRepositoryProvider = Provider<CatalogRepository>(
    (ref) => CatalogRepository(api: ref.watch(apiClientProvider)));

// ---- Session --------------------------------------------------------------

sealed class SessionState {}
class SessionUnknown extends SessionState {}
class SessionAnonymous extends SessionState {}
class SessionAuthenticated extends SessionState {
  SessionAuthenticated(this.user);
  final AppUser user;
}

final sessionProvider =
    StateNotifierProvider<SessionNotifier, SessionState>((ref) => SessionNotifier(ref));

class SessionNotifier extends StateNotifier<SessionState> {
  SessionNotifier(this._ref) : super(SessionUnknown()) {
    _bootstrap();
    // Wire 401 -> logout.
    _ref.read(settingsProvider).onUnauthorized = () async {
      await _ref.read(authRepositoryProvider).logout();
      state = SessionAnonymous();
    };
  }
  final Ref _ref;

  Future<void> _bootstrap() async {
    final api = _ref.read(apiClientProvider);
    await api.loadToken();
    final token = await _ref.read(tokenStoreProvider).readToken();
    if (token == null) {
      state = SessionAnonymous();
      return;
    }
    try {
      final user = await _ref.read(authRepositoryProvider).me();
      state = SessionAuthenticated(user);
    } catch (_) {
      await _ref.read(tokenStoreProvider).deleteToken();
      api.setToken(null);
      state = SessionAnonymous();
    }
  }

  Future<AppUser> login(String phone, String code) async {
    final user = await _ref.read(authRepositoryProvider).verifyOtp(phone, code);
    state = SessionAuthenticated(user);
    return user;
  }

  Future<void> refresh() async {
    if (state is SessionAuthenticated) {
      try {
        final user = await _ref.read(authRepositoryProvider).me();
        state = SessionAuthenticated(user);
      } catch (_) {}
    }
  }

  Future<void> logout() async {
    await _ref.read(authRepositoryProvider).logout();
    state = SessionAnonymous();
  }

  /// After merchant/driver registration completes server-side.
  Future<void> reloadRole() => _bootstrap();
}

// ---- Locale & theme -------------------------------------------------------

final localeProvider =
    StateNotifierProvider<LocaleNotifier, Locale>((ref) => LocaleNotifier(ref));

class LocaleNotifier extends StateNotifier<Locale> {
  LocaleNotifier(this._ref) : super(const Locale('fr')) {
    _load();
  }
  final Ref _ref;

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    final code = prefs.getString('locale') ?? 'fr';
    state = Locale(code);
    _apply();
  }

  Future<void> set(Locale l) async {
    state = l;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('locale', l.languageCode);
    _apply();
  }

  void _apply() {
    _ref.read(settingsProvider).localeCode = state.languageCode;
  }
}

final themeModeProvider =
    StateNotifierProvider<ThemeModeNotifier, ThemeMode>((ref) => ThemeModeNotifier());

class ThemeModeNotifier extends StateNotifier<ThemeMode> {
  ThemeModeNotifier() : super(ThemeMode.system) {
    _load();
  }
  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    final v = prefs.getString('theme_mode');
    if (v != null) {
      state = ThemeMode.values.firstWhere((e) => e.name == v, orElse: () => ThemeMode.system);
    }
  }

  Future<void> set(ThemeMode m) async {
    state = m;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('theme_mode', m.name);
  }
}

/// Brand config fetched from GET /config (falls back to compile-time defaults).
final brandConfigProvider = FutureProvider<BrandConfig>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final data = await api.getCached('config', cacheKey: 'config');
    return BrandConfig.fromApi(Map<String, dynamic>.from(data as Map));
  } catch (_) {
    return const BrandConfig(
      appName: 'Marsaly',
      currencySymbol: 'د.م.',
      taglines: {
        'ar': 'كلشي لحد باب دارك',
        'fr': 'Tout, livré chez vous',
        'en': 'Everything, delivered to your door',
      },
    );
  }
});

// ---- Feature repositories -------------------------------------------------

final orderRepositoryProvider =
    Provider<OrderRepository>((ref) => OrderRepository(api: ref.watch(apiClientProvider)));
final merchantRepositoryProvider =
    Provider<MerchantRepository>((ref) => MerchantRepository(api: ref.watch(apiClientProvider)));
final driverRepositoryProvider =
    Provider<DriverRepository>((ref) => DriverRepository(api: ref.watch(apiClientProvider)));
