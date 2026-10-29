import 'dart:async';

/// Realtime channel subscriptions. The backend broadcasts on Pusher-compatible
/// channels (private-order.{id}, private-driver.{id}). When PUSHER_KEY is not
/// provided via --dart-define, we transparently fall back to HTTP polling so
/// the app remains fully functional in dev.
class PushEvent {
  PushEvent(this.event, this.data);
  final String event;
  final Map<String, dynamic> data;
}

class PusherService {
  PusherService({String? key, String? cluster})
      : _key = key ?? const String.fromEnvironment('PUSHER_KEY'),
        _cluster = cluster ?? const String.fromEnvironment('PUSHER_CLUSTER', defaultValue: 'mt1');

  final String _key;
  final String _cluster;
  final _streams = <String, StreamController<PushEvent>>{};
  Timer? _pollTimer;


  bool get hasRealtime => _key.isNotEmpty;

  Stream<PushEvent> subscribe(String channel) {
    final ctrl = _streams.putIfAbsent(channel, () => StreamController<PushEvent>.broadcast());
    _ensureRunning();
    return ctrl.stream;
  }

  void unsubscribe(String channel) {
    _streams.remove(channel)?.dispose();
    if (_streams.isEmpty) _stop();
  }

  void Function()? _onEvent; // invoked for every event (realtime or poll)

  void setOnEvent(void Function(String channel, PushEvent e) cb) => _onEvent = cb;

  void _ensureRunning() {
    if (!hasRealtime && _pollTimer == null) {
      // Fallback: notify listeners every 8s; screens re-fetch their data.
      _pollTimer = Timer.periodic(const Duration(seconds: 8), (_) {
        for (final entry in _streams.entries) {
          final ev = PushEvent('poll', const {});
          _onEvent?.call(entry.key, ev);
          if (!entry.value.isClosed) entry.value.add(ev);
        }
      });
    }
  }

  void _stop() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  /// Hook for a real pusher_channels_flutter connection when configured.
  void ingestFromPusher(String channel, String event, Map<String, dynamic> data) {
    final ev = PushEvent(event, data);
    _onEvent?.call(channel, ev);
    _streams[channel]?.add(ev);
  }

  void dispose() {
    _stop();
    for (final c in _streams.values) {
      c.close();
    }
    _streams.clear();
  }
}
