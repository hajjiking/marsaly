import 'package:intl/intl.dart';

String formatMoney(num v, String symbol) {
  final n = NumberFormat('#,##0.00', 'fr').format(v);
  return '$n $symbol';
}

String formatDistanceKm(double? km) =>
    km == null ? '' : (km < 1 ? '${(km * 1000).round()} m' : '${km.toStringAsFixed(1)} km');

String relativeTime(DateTime? dt, String locale) {
  if (dt == null) return '';
  final diff = DateTime.now().difference(dt);
  final loc = locale == 'ar' ? 'ar' : (locale == 'fr' ? 'fr' : 'en');
  if (diff.inMinutes < 1) return locale == 'ar' ? 'الآن' : (loc == 'fr' ? "à l'instant" : 'now');
  if (diff.inHours < 1) return DateFormat.Hm(loc).format(dt);
  if (diff.inDays < 7) return DateFormat.EEEE(loc).add_Hm().format(dt);
  return DateFormat.yMMMd(loc).add_Hm().format(dt);
}
