import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// Tiny offline-friendly JSON cache (menus, last orders, config).
class CacheBox {
  static Future<void> put(String key, Object? json) async {
    if (json == null) return;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('cache:$key', jsonEncode(json));
  }

  static Future<dynamic> get(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('cache:$key');
    return raw == null ? null : jsonDecode(raw);
  }

  static Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    for (final k in prefs.getKeys().where((k) => k.startsWith('cache:'))) {
      await prefs.remove(k);
    }
  }
}
