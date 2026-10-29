import '../models/dto.dart';

/// Merchant panel endpoints.
class MerchantRepository {
  MerchantRepository({required this.api});
  final dynamic api;

  Future<Merchant> profile() async {
    final data = await api.get('merchant/profile');
    return Merchant.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Merchant> mine() async {
    final data = await api.get('merchants/me');
    return Merchant.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> updateProfile(Map<String, dynamic> body) => api.put('merchant/profile', body: body);
  Future<void> toggleBusy() => api.post('merchant/busy');
  Future<void> updateHours(List<Map<String, dynamic>> hours) => api.put('merchant/hours', body: {'hours': hours});

  Future<List<Order>> orders({String? status}) async {
    final data = await api.getCached('merchant/orders',
        query: {if (status != null) 'status': status}, cacheKey: 'm_orders');
    final list = data is Map ? data['data'] : data;
    return (list as List).map((e) => Order.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<Order> setStatus(int orderId, String status, {String? reason}) async {
    final data = await api.post('merchant/orders/$orderId/status',
        body: {'status': status, if (reason != null) 'reason': reason});
    return Order.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<MenuSection>> menu() async {
    final data = await api.getCached('merchant/menu', cacheKey: 'm_menu');
    final list = data is Map ? data['sections'] : data;
    return (list as List).map((e) => MenuSection.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<void> addSection(String name) => api.post('merchant/sections', body: {'name': name});
  Future<void> updateSection(int id, String name) => api.put('merchant/sections/$id', body: {'name': name});
  Future<void> deleteSection(int id) => api.delete('merchant/sections/$id');

  Future<Product> saveProduct(Map<String, dynamic> body, {int? id}) async {
    final data = id == null
        ? await api.post('merchant/products', body: body)
        : await api.put('merchant/products/$id', body: body);
    return Product.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> deleteProduct(int id) => api.delete('merchant/products/$id');

  Future<List<Review>> reviews() async {
    final data = await api.getCached('merchant/reviews', cacheKey: 'm_reviews');
    final list = data is Map ? data['data'] : data;
    return (list as List).map((e) => Review.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<void> replyReview(int id, String reply) => api.post('merchant/reviews/$id/reply', body: {'reply': reply});

  Future<MerchantEarnings> earnings() async {
    final data = await api.get('merchant/earnings');
    return MerchantEarnings.fromJson(Map<String, dynamic>.from(data as Map));
  }
}

/// Driver panel endpoints.
class DriverRepository {
  DriverRepository({required this.api});
  final dynamic api;

  Future<Map<String, dynamic>> me() async {
    final data = await api.get('driver/me');
    return Map<String, dynamic>.from(data as Map);
  }

  Future<void> setAvailability(bool online) => api.post('driver/availability', body: {'is_online': online});

  /// Fire-and-forget location ping (throttled server-side).
  Future<void> pushLocation(double lat, double lng, {double heading = 0, double speed = 0}) =>
      api.post('driver/location', body: {'lat': lat, 'lng': lng, 'heading': heading, 'speed': speed});

  Future<List<DriverOffer>> offers() async {
    final data = await api.get('driver/offers');
    final list = data is Map ? data['offers'] : data;
    return (list as List).map((e) => DriverOffer.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<List<ActiveDelivery>> active() async {
    final data = await api.get('driver/active');
    final list = data is Map ? data['deliveries'] : data;
    return (list as List).map((e) => ActiveDelivery.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<List<ActiveDelivery>> history() async {
    final data = await api.getCached('driver/history', cacheKey: 'd_history');
    final list = data is Map ? data['deliveries'] : data;
    return (list as List).map((e) => ActiveDelivery.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<void> accept(int deliveryId) => api.post('driver/deliveries/$deliveryId/accept');
  Future<void> decline(int deliveryId) => api.post('driver/deliveries/$deliveryId/decline');
  Future<void> action(int deliveryId, String action, {String? otp}) =>
      api.post('driver/deliveries/$deliveryId/action', body: {'action': action, if (otp != null) 'delivery_otp': otp});
  Future<void> failed(int deliveryId, String reason) =>
      api.post('driver/deliveries/$deliveryId/failed', body: {'reason': reason});

  Future<CashSummary> cash() async {
    final data = await api.get('driver/cash');
    return CashSummary.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Earnings> earnings() async {
    final data = await api.get('driver/earnings');
    return Earnings.fromJson(Map<String, dynamic>.from(data as Map));
  }
}
