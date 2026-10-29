import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../core/providers/app_providers.dart';
import '../core/providers/cart_provider.dart';
import '../l10n/app_localizations.dart';

/// Bottom-navigation shell for the client app. Merchant/driver roles get a
/// dedicated panel instead (redirected from Profile/Home via role check).
class RootShell extends ConsumerWidget {
  const RootShell({required this.state, required this.child, super.key});
  final GoRouterState state;
  final Widget child;

  static const _tabs = ['/home', '/search', '/cart', '/orders', '/profile'];

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final loc = GoRouterState.of(context).matchedLocation;
    final idx = _tabs.firstWhere((t) => loc.startsWith(t), orElse: () => '/home');
    final tabIdx = _tabs.indexOf(idx);
    final cartCount = ref.watch(cartProvider).cart.count;
    final l = S.of(context);

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: tabIdx,
        onDestinationSelected: (i) => context.go(_tabs[i]),
        destinations: [
          NavigationDestination(icon: const Icon(Icons.storefront_outlined), selectedIcon: const Icon(Icons.storefront), label: l.navHome),
          NavigationDestination(icon: const Icon(Icons.search_outlined), label: l.navSearch),
          NavigationDestination(
            icon: Badge(count: cartCount == 0 ? null : cartCount, isLabelVisible: cartCount > 0, child: const Icon(Icons.shopping_bag_outlined)),
            selectedIcon: Badge(count: cartCount == 0 ? null : cartCount, isLabelVisible: cartCount > 0, child: const Icon(Icons.shopping_bag)),
            label: l.navCart),
          NavigationDestination(icon: const Icon(Icons.receipt_long_outlined), selectedIcon: const Icon(Icons.receipt_long), label: l.navOrders),
          NavigationDestination(icon: const Icon(Icons.person_outline), selectedIcon: const Icon(Icons.person), label: l.navProfile),
        ],
      ),
    );
  }
}

/// Role-aware home redirect helper used by screens.
String homeForRole(String role) {
  switch (role) {
    case 'merchant':
      return '/m/home';
    case 'driver':
      return '/d/home';
    default:
      return '/home';
  }
}
