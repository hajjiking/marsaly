import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// Local notification channel setup + simple fire helpers (driver offers,
/// order status updates while app is foregrounded).
class LocalNotifications {
  static final _plugin = FlutterLocalNotificationsPlugin();
  static const _channelId = 'marsaly_general';
  static bool _initialized = false;

  static Future<void> init() async {
    if (_initialized) return;
    _initialized = true;
    await _plugin.initialize(
      const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
        iOS: DarwinInitializationSettings(),
      ),
    );
    const android = AndroidNotificationChannel(
      _channelId, 'Marsaly', description: 'Order and delivery updates',
      importance: Importance.high, playSound: true);
    await _plugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(android);
  }

  static Future<void> show(String title, String body) async {
    await init();
    await _plugin.show(
      DateTime.now().millisecond % 10000,
      title,
      body,
      const NotificationDetails(
        android: AndroidNotificationDetails(_channelId, 'Marsaly',
            importance: Importance.high, priority: Priority.high),
        iOS: DarwinNotificationDetails(),
      ),
    );
  }
}
