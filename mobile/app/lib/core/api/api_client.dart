import 'dart:async';
import 'dart:io';

import 'package:dio/dio.dart';

import '../../config/flavor.dart';
import '../api/cache_box.dart';
import '../providers/settings_provider.dart';
import 'api_exception.dart';
import 'token_store.dart';

/// Thin wrapper over Dio for the Marsaly REST API (/api/v1).
/// Interceptors: bearer token, Accept-Language, retry on network flake.
class ApiClient {
  ApiClient({required this.tokenStore, required this.settings, Dio? dio})
      : dio = dio ??
            Dio(BaseOptions(
              baseUrl: Flavor.current.baseUrl,
              connectTimeout: const Duration(seconds: 15),
              receiveTimeout: const Duration(seconds: 30),
              headers: {'Accept': 'application/json'},
            )) {
    this.dio.interceptors.add(AuthInterceptor(this));
  }

  final Dio dio;
  final TokenStore tokenStore;
  final SettingsProvider settings;

  String? _token;
  bool _unauthorizedNotified = false;

  Future<void> loadToken() async => _token = await tokenStore.readToken();
  void setToken(String? t) {
    _token = t;
    _unauthorizedNotified = t == null ? false : _unauthorizedNotified;
  }

  /// GET with offline cache fallback (used for catalogs/menus/orders list).
  Future<dynamic> getCached(String path,
      {Map<String, dynamic>? query, String? cacheKey}) async {
    try {
      final data = await get(path, query: query);
      if (cacheKey != null) unawaited(_writeCache(cacheKey, data));
      return data;
    } on ApiException catch (e) {
      if (e.isNetwork && cacheKey != null) {
        final cached = await readCache(cacheKey);
        if (cached != null) return cached;
      }
      rethrow;
    }
  }

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) =>
      _send(() => dio.get<dynamic>(path, queryParameters: query));

  Future<dynamic> post(String path, {Object? body, Map<String, dynamic>? query}) =>
      _send(() => dio.post<dynamic>(path, data: body, queryParameters: query));

  Future<dynamic> put(String path, {Object? body}) =>
      _send(() => dio.put<dynamic>(path, data: body));

  Future<dynamic> patch(String path, {Object? body}) =>
      _send(() => dio.patch<dynamic>(path, data: body));

  Future<dynamic> delete(String path, {Object? body}) =>
      _send(() => dio.delete<dynamic>(path, data: body));

  /// Multipart upload (documents, product images).
  Future<dynamic> upload(String path, Map<String, dynamic> fields,
      {Map<String, String> files = const {}, void Function(int, int)? onSendProgress}) async {
    final form = FormData.fromMap({...fields});
    for (final e in files.entries) {
      form.files.add(MapEntry(
        e.key,
        await MultipartFile.fromFile(e.value, filename: e.value.split('/').last),
      ));
    }
    return _send(() => dio.post<dynamic>(path,
        data: form, onSendProgress: onSendProgress));
  }

  static Future<dynamic> readCache(String key) => CacheBox.get(key);
  Future<void> _writeCache(String key, dynamic data) => CacheBox.put(key, data);

  Future<dynamic> _send(Future<Response<dynamic>> Function() request,
      {int retries = 2}) async {
    var attempt = 0;
    while (true) {
      try {
        final res = await request();
        return _unwrap(res.data);
      } on DioException catch (e) {
        final transient = e.type == DioExceptionType.connectionTimeout ||
            e.type == DioExceptionType.receiveTimeout ||
            e.type == DioExceptionType.sendTimeout ||
            e.type == DioExceptionType.connectionError;
        if (transient && attempt < retries) {
          attempt++;
          await Future.delayed(Duration(milliseconds: 400 * attempt));
          continue;
        }
        throw _map(e);
      } on SocketException {
        if (attempt < retries) {
          attempt++;
          await Future.delayed(Duration(milliseconds: 400 * attempt));
          continue;
        }
        throw ApiException('Network error');
      }
    }
  }

  dynamic _unwrap(dynamic raw) {
    if (raw is Map && raw.containsKey('success')) {
      if (raw['success'] == true) return raw['data'];
      final errs = (raw['errors'] as Map?)?.cast<String, dynamic>() ?? {};
      throw ApiException(
        (raw['message'] ?? 'Error').toString(),
        fieldErrors: errs.map((k, v) =>
            MapEntry(k, (v as List?)?.map((e) => e.toString()).toList() ?? [])),
      );
    }
    return raw;
  }

  ApiException _map(DioException e) {
    final status = e.response?.statusCode;
    if (status == 401) {
      if (!_unauthorizedNotified) {
        _unauthorizedNotified = true;
        settings.onUnauthorized?.call();
      }
      return ApiException('Session expired', statusCode: 401);
    }
    final data = e.response?.data;
    if (data is Map && data['message'] != null) {
      final errs = (data['errors'] as Map?)?.cast<String, dynamic>() ?? {};
      return ApiException(
        data['message'].toString(),
        statusCode: status,
        fieldErrors: errs.map((k, v) =>
            MapEntry(k, (v as List?)?.map((x) => x.toString()).toList() ?? [])),
      );
    }
    if (status == 429) return ApiException('Too many attempts, wait a moment', statusCode: 429);
    return ApiException('Network error', statusCode: status);
  }
}

class AuthInterceptor extends Interceptor {
  AuthInterceptor(this.client);
  final ApiClient client;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    final t = client._token;
    if (t != null) options.headers['Authorization'] = 'Bearer $t';
    options.headers['Accept-Language'] = client.settings.localeCode;
    handler.next(options);
  }
}
