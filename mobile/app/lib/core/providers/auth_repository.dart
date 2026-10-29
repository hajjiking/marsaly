import '../api/api_exception.dart';
import '../api/token_store.dart';
import '../models/dto.dart';

/// Data layer for auth endpoints (OTP login, profile, logout).
class AuthRepository {
  AuthRepository({required this.api, required this.tokenStore});
  final dynamic api; // ApiClient (typed loosely to avoid import cycle in tests)
  final TokenStore tokenStore;

  Future<String?> cachedToken() => tokenStore.readToken();

  Future<Map<String, dynamic>> requestOtp(String phone) async {
    final data = await api.post('auth/otp/request', body: {'phone': phone});
    return Map<String, dynamic>.from(data as Map);
  }

  Future<AppUser> verifyOtp(String phone, String code) async {
    final data = await api.post('auth/otp/verify', body: {'phone': phone, 'code': code});
    final map = Map<String, dynamic>.from(data as Map);
    final token = map['token']?.toString();
    if (token != null) {
      await tokenStore.writeToken(token);
      api.setToken(token);
    }
    await tokenStore.writePhone(phone);
    return AppUser.fromJson(Map<String, dynamic>.from(map['user'] as Map));
  }

  Future<AppUser> me() async {
    final data = await api.get('me');
    return AppUser.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<AppUser> updateProfile({String? name}) async {
    final data = await api.put('me', body: {if (name != null) 'name': name});
    return AppUser.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> logout() async {
    try {
      await api.post('logout');
    } on ApiException {
      // ignore server errors; clear locally anyway
    }
    await tokenStore.deleteToken();
    api.setToken(null);
  }
}
