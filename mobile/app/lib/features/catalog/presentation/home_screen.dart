import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/models/dto.dart';
import '../../../core/providers/app_providers.dart';
import '../../../core/providers/location_provider.dart';
import '../../../l10n/app_localizations.dart';
import '../../common/presentation/widgets.dart';

final categoriesProvider = FutureProvider<List<Category>>(
    (ref) => ref.watch(catalogRepositoryProvider).categories());

final homeFeedProvider = FutureProvider.family<List<Merchant>, ({int? categoryId})>(
    (ref, arg) async {
  final loc = ref.watch(locationProvider);
  return ref.watch(catalogRepositoryProvider).merchants(
      categoryId: arg.categoryId, lat: loc?.lat, lng: loc?.lng);
});

final bannersProvider =
    FutureProvider<List<Banner>>((ref) => ref.watch(catalogRepositoryProvider).banners());
final announcementsProvider = FutureProvider<List<Announcement>>(
    (ref) => ref.watch(catalogRepositoryProvider).announcements());

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});
  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  int? _categoryId;

  @override
  Widget build(BuildContext context) {
    final l = S.of(context);
    final brand = ref.watch(brandConfigProvider).valueOrNull;
    final ann = ref.watch(announcementsProvider).valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(brand?.appName ?? 'Marsaly'),
          Text(brand?.taglines[Localizations.localeOf(context).languageCode] ?? l.appTagline,
              style: TextStyle(fontSize: 11, color: Theme.of(context).colorScheme.onSurfaceVariant)),
        ]),
        actions: [
          IconButton(icon: const Icon(Icons.notifications_outlined), onPressed: () => context.push('/notifications')),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(bannersProvider);
          ref.invalidate(categoriesProvider);
          ref.invalidate(announcementsProvider);
          ref.invalidate(homeFeedProvider);
        },
        child: ListView(
          padding: const EdgeInsets.only(bottom: 24),
          children: [
            if (ann != null && ann.isNotEmpty)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                child: Card(
                  color: Theme.of(context).colorScheme.secondaryContainer,
                  child: ListTile(
                      leading: const Icon(Icons.campaign_outlined),
                      title: Text(ann.first.title),
                      subtitle: Text(ann.first.body, maxLines: 2, overflow: TextOverflow.ellipsis),
                      onTap: () => showDialog(
                          context: context,
                          builder: (_) => AlertDialog(
                              title: Text(ann.first.title),
                              content: SingleChildScrollView(child: Text(ann.first.body))))),
                ),
              ),
            const SizedBox(height: 8),
            _bannerCarousel(),
            SectionTitle(l.categories, action: TextButton(onPressed: () => context.go('/search'), child: Text(l.seeAll))),
            _categoryChips(),
            SectionTitle(_categoryId == null ? l.featured : l.categories),
            ..._merchantList(),
          ],
        ),
      ),
    );
  }

  Widget _bannerCarousel() {
    final banners = ref.watch(bannersProvider).valueOrNull ?? [];
    if (banners.isEmpty) return const SizedBox(height: 8);
    return SizedBox(
      height: 140,
      child: PageView.builder(
        itemCount: banners.length,
        controller: PageController(viewportFraction: 0.92),
        padEnds: false,
        itemBuilder: (_, i) => Padding(
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: Stack(fit: StackFit.expand, children: [
              NetImage(banners[i].image),
              Positioned(
                left: 0, right: 0, bottom: 0,
                child: Container(
                  padding: const EdgeInsets.all(10),
                  color: Colors.black45,
                  child: Text(banners[i].title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                ),
              ),
            ]),
          ),
        ),
      ),
    );
  }

  Widget _categoryChips() {
    final cats = ref.watch(categoriesProvider).valueOrNull ?? [];
    final isAr = Localizations.localeOf(context).languageCode == 'ar';
    return SizedBox(
      height: 48,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
            child: ChoiceChip(
              label: Text(S.of(context).featured),
              selected: _categoryId == null,
              onSelected: (_) => setState(() => _categoryId = null),
            ),
          ),
          for (final c in cats)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
              child: ChoiceChip(
                avatar: Icon(_iconFor(c.icon)),
                label: Text(isAr && c.nameAr.isNotEmpty ? c.nameAr : c.name),
                selected: _categoryId == c.id,
                onSelected: (_) => setState(() => _categoryId = c.id),
              ),
            ),
        ],
      ),
    );
  }

  static IconData _iconFor(String? name) {
    const map = {
      'restaurant': Icons.restaurant, 'grocery': Icons.local_grocery_store_outlined,
      'pharmacy': Icons.local_pharmacy_outlined, 'bakery': Icons.cake_outlined,
      'flowers': Icons.local_florist_outlined, 'electronics': Icons.devices_other_outlined,
      'fashion': Icons.checkroom_outlined, 'home_service': Icons.handyman_outlined,
    };
    return map[name] ?? Icons.storefront_outlined;
  }

  List<Widget> _merchantList() {
    final feed = ref.watch(homeFeedProvider(categoryId: _categoryId));
    if (feed.isLoading && ref.watch(locationProvider) == null) return const [LoadingList()];
    return feed.when(
      loading: () => const [SizedBox(height: 40, child: Center(child: CircularProgressIndicator()))],
      error: (e, _) => [ErrorRetry(error: e, onRetry: () => ref.invalidate(homeFeedProvider))],
      data: (items) {
        if (items.isEmpty) return [Center(child: Padding(padding: const EdgeInsets.all(32), child: Text(S.of(context).noResults)))];
        return [for (final m in items) _MerchantTile(merchant: m)];
      },
    );
  }
}

class _MerchantTile extends StatelessWidget {
  const _MerchantTile({required this.merchant});
  final Merchant merchant;
  @override
  Widget build(BuildContext context) {
    final l = S.of(context);
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => context.push('/merchant/${merchant.id}'),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(children: [
            NetImage(merchant.logo, width: 64, height: 64),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(merchant.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 4),
                Row(children: [
                  const Icon(Icons.star_rounded, size: 16, color: Colors.amber),
                  Text(' ${merchant.rating.toStringAsFixed(1)}'),
                  const SizedBox(width: 10),
                  if (merchant.distanceKm != null)
                    Text(formatDistance(merchant.distanceKm)),
                  const SizedBox(width: 10),
                  if (merchant.prepMinutes != null) Text('· ${l.minutes(merchant.prepMinutes!)}'),
                ]),
                const SizedBox(height: 4),
                Wrap(spacing: 6, children: [
                  if (merchant.isOpen)
                    _pill(context, l.openNow, Colors.green)
                  else
                    _pill(context, l.temporarilyClosed, Colors.redAccent),
                  if (merchant.deliveryFee != null)
                    _pill(context, '${merchant.deliveryFee!.toStringAsFixed(0)} MAD', Theme.of(context).colorScheme.primary),
                ]),
              ]),
            ),
            const Icon(Icons.chevron_right),
          ]),
        ),
      ),
    );
  }

  static String formatDistance(double? km) =>
      km == null ? '' : (km < 1 ? '${(km * 1000).round()} m' : '${km.toStringAsFixed(1)} km');

  Widget _pill(BuildContext context, String text, Color color) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
        decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(8)),
        child: Text(text, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w700)),
      );
}
