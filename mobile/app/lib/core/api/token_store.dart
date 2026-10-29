import 'package:flutter_secure_storage/flutter_secure_storage';

/// Secure persistence for the Sanctum bearer token + last known phone.
class TokenStore {
  TokenStore({FlutterSecureStorage? storage})
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
            );

  final FlutterSecureStorage _storage;
  static const _kToken = 'marsaly_token';
  static const _kPhone = 'marsaly_phone';

  Future<String?> readToken() => _storage.read(key: _kToken);
  Future<void> writeToken(String t) => _storage.write(key: _kToken, value: t);
  Future<void> deleteToken() => _storage.delete(key: _kToken);

  Future<String?> readPhone() => _storage.read(key: _kPhone);
  Future<void> writePhone(String p) => _storage.write(key: _kPhone, value: p);
}
