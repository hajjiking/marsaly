import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Locale + theme persistence and the hook used by ApiClient on 401.
class SettingsProvider {
  String localeCode = 'fr';
  Future<void> Function()? onUnauthorized;

  Locale get locale => Locale(localeCode);
}
