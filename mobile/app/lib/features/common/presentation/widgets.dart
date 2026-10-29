import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';

import '../../../core/providers/app_providers.dart';
import '../../../l10n/app_localizations.dart';

/// Money display with currency symbol from backend brand config.
class MoneyText extends ConsumerWidget {
  const MoneyText(this.value, {this.style, this.compact = false, super.key});
  final double value;
  final TextStyle? style;
  final bool compact;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sym = ref.watch(brandConfigProvider).valueOrNull?.currencySymbol ?? 'MAD';
    final txt = compact ? value.toStringAsFixed(0) : value.toStringAsFixed(2);
    return Text('$txt $sym', style: style);
  }
}

String money(double v, String symbol) => '${v.toStringAsFixed(2)} $symbol';

class BrandLogo extends StatelessWidget {
  const BrandLogo({this.size = 64, super.key});
  final double size;
  @override
  Widget build(BuildContext context) {
    // Vector placeholder mark (orange rounded square + white M + teal clock).
    return Container(
      width: size, height: size,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primary,
        borderRadius: BorderRadius.circular(size * 0.22),
      ),
      child: Icon(Icons.delivery_dining_rounded, size: size * 0.6, color: Colors.white),
    );
  }
}

class ErrorRetry extends StatelessWidget {
  const ErrorRetry({required this.error, this.onRetry, super.key});
  final Object error;
  final VoidCallback? onRetry;
  @override
  Widget build(BuildContext context) {
    final l = S.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.cloud_off_rounded, size: 48),
          const SizedBox(height: 12),
          Text(error is Exception ? l.errorGeneric : '$error', textAlign: TextAlign.center),
          if (onRetry != null) ...[
            const SizedBox(height: 16),
            FilledButton(onPressed: onRetry, child: Text(l.retry)),
          ],
        ]),
      ),
    );
  }
}

class LoadingList extends StatelessWidget {
  const LoadingList({super.key});
  @override
  Widget build(BuildContext context) => ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: 6,
      itemBuilder: (_, __) => Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Shimmer.fromColors(
              baseColor: Colors.grey.shade300,
              highlightColor: Colors.grey.shade100,
              child: Container(height: 84, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16))),
            ),
          ));
}

class NetImage extends StatelessWidget {
  const NetImage(this.url, {this.width, this.height, this.fit = BoxFit.cover, this.radius = 12, super.key});
  final String? url;
  final double? width, height;
  final BoxFit fit;
  final double radius;
  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    if (url == null || url!.isEmpty) {
      return Container(
        width: width, height: height,
        decoration: BoxDecoration(color: scheme.surfaceContainerHighest, borderRadius: BorderRadius.circular(radius)),
        child: Icon(Icons.storefront, color: scheme.outline),
      );
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: CachedNetworkImage(imageUrl: url!, width: width, height: height, fit: fit,
        placeholder: (_, __) => Container(width: width, height: height, color: scheme.surfaceContainerHighest),
        errorWidget: (_, __, ___) => Container(width: width, height: height, color: scheme.surfaceContainerHighest, child: const Icon(Icons.broken_image_outlined)),
      ),
    );
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});
  final String status;
  static const _colors = <String, Color>{
    'pending': Colors.amber,
    'accepted': Colors.blue,
    'preparing': Colors.indigo,
    'ready': Colors.teal,
    'driver_assigned': Colors.cyan,
    'picked_up': Colors.deepOrange,
    'on_the_way': Colors.orange,
    'delivered': Colors.green,
    'cancelled': Colors.redAccent,
    'rejected': Colors.red,
    'failed': Colors.brown,
    'new': Colors.amber,
    'assigned': Colors.cyan,
  };
  @override
  Widget build(BuildContext context) {
    final l = S.of(context);
    final label = switch (status) {
      'pending' => l.statusPending, 'accepted' => l.statusAccepted,
      'preparing' => l.statusPreparing, 'ready' => l.statusReady,
      'driver_assigned' => l.statusDriverAssigned, 'picked_up' => l.statusPickedUp,
      'on_the_way' => l.statusOnTheWay, 'delivered' => l.statusDelivered,
      'cancelled' => l.statusCancelled, 'rejected' => l.statusRejected,
      'failed' => l.statusFailed, 'new' => l.statusPending, 'assigned' => l.statusDriverAssigned,
      _ => status,
    };
    final c = _colors[status] ?? Colors.grey;
    return Chip(label: Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 12)),
        labelPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
        backgroundColor: c, elevation: 0);
  }
}

void toast(BuildContext context, String msg, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(msg), backgroundColor: error ? Theme.of(context).colorScheme.error : null));
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.text, {this.action, super.key});
  final String text;
  final Widget? action;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
        child: Row(children: [
          Expanded(child: Text(text, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold))),
          if (action != null) action!,
        ]),
      );
}
