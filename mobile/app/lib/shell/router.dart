import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../core/providers/app_providers.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/catalog/presentation/home_screen.dart';
import '../features/catalog/presentation/merchant_screen.dart';
import '../features/catalog/presentation/search_screen.dart';
import '../features/cart/presentation/cart_screen.dart';
import '../features/cart/presentation/checkout_screen.dart';
import '../features/common/presentation/address_picker_screen.dart';
import '../features/common/presentation/address_form_screen.dart';
import '../features/common/presentation/notifications_screen.dart';
import '../features/common/presentation/profile_screen.dart';
import '../features/common/presentation/review_screen.dart';
import '../features/common/presentation/support_screen.dart';
import '../features/common/presentation/register_merchant_screen.dart';
import '../features/common/presentation/register_driver_screen.dart';
import '../features/driver/presentation/driver_home_screen.dart';
import '../features/driver/presentation/driver_history_screen.dart';
import '../features/driver/presentation/driver_settlement_screen.dart';
import '../features/merchant/presentation/merchant_home_screen.dart';
import '../features/merchant/presentation/merchant_menu_screen.dart';
import '../features/merchant/presentation/merchant_reviews_screen.dart';
import '../features/merchant/presentation/merchant_settings_screen.dart';
import '../features/orders/presentation/order_detail_screen.dart';
import '../features/orders/presentation/orders_screen.dart';
import 'root_shell.dart';

final _rootKey = GlobalKey<NavigatorState>();

final routerProvider = Provider<GoRouter>((ref) {
  final session = ref.watch(sessionProvider);
  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: '/home',
    redirectLimit: 5,
    redirect: (context, state) {
      final loc = state.matchedLocation;
      final isLogin = loc == '/login';
      if (session is SessionUnknown) return null;
      final authed = session is SessionAuthenticated;
      if (!authed && !isLogin) return '/login';
      if (authed && isLogin) return '/home';
      return null;
    },
    routes: [
      ShellRoute(
        builder: (context, state, child) => RootShell(state: state, child: child),
        routes: [
          GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
          GoRoute(path: '/search', builder: (_, __) => const SearchScreen()),
          GoRoute(path: '/cart', builder: (_, __) => const CartScreen()),
          GoRoute(path: '/orders', builder: (_, __) => const OrdersScreen()),
          GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
        ],
      ),
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(
          path: '/merchant/:id',
          builder: (_, s) => MerchantScreen(id: int.parse(s.pathParameters['id']!))),
      GoRoute(
          path: '/checkout',
          builder: (_, __) => const CheckoutScreen(),
          routes: [
            GoRoute(
                path: 'address',
                parentNavigatorKey: _rootKey,
                builder: (_, s) => AddressPickerScreen(
                    onSelect: s.extra is void Function(int) ? s.extra as void Function(int) : null)),
          ]),
      GoRoute(path: '/address/new', builder: (_, __) => const AddressFormScreen()),
      GoRoute(
          path: '/address/:id',
          builder: (_, s) => AddressFormScreen(id: int.parse(s.pathParameters['id']!))),
      GoRoute(
          path: '/order/:id',
          builder: (_, s) => OrderDetailScreen(orderId: int.parse(s.pathParameters['id']!)),
          routes: [
            GoRoute(
                path: 'review',
                parentNavigatorKey: _rootKey,
                builder: (_, st) => ReviewScreen(orderId: int.parse(st.pathParameters['id']!))),
          ]),
      GoRoute(path: '/notifications', builder: (_, __) => const NotificationsScreen()),
      GoRoute(path: '/support', builder: (_, __) => const SupportScreen()),
      GoRoute(path: '/register-merchant', builder: (_, __) => const RegisterMerchantScreen()),
      GoRoute(path: '/register-driver', builder: (_, __) => const RegisterDriverScreen()),
      GoRoute(path: '/m/home', builder: (_, __) => const MerchantHomeScreen()),
      GoRoute(path: '/m/menu', builder: (_, __) => const MerchantMenuScreen()),
      GoRoute(path: '/m/reviews', builder: (_, __) => const MerchantReviewsScreen()),
      GoRoute(path: '/m/settings', builder: (_, __) => const MerchantSettingsScreen()),
      GoRoute(path: '/d/home', builder: (_, __) => const DriverHomeScreen()),
      GoRoute(path: '/d/history', builder: (_, __) => const DriverHistoryScreen()),
      GoRoute(path: '/d/settlements', builder: (_, __) => const DriverSettlementScreen()),
    ],
  );
});
