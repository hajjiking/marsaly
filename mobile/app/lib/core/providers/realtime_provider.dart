import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../utils/pusher_service.dart';

final pusherProvider = Provider<PusherService>((ref) {
  final s = PusherService();
  ref.onDispose(s.dispose);
  return s;
});
