import '../models/dto.dart';

class OrderRepository {
  OrderRepository({required this.api});
  final dynamic api;

  Future<Cart> cart() async {
    final data = await api.get('cart');
    return Cart.fromJson(data == null ? null : Map<String, dynamic>.from(data as Map));
  }

  Future<Cart> addItem(int productId, {int qty = 1, List<int> optionValueIds = const [], String? note}) async {
    final data = await api.post('cart/items', body: {
      'product_id': productId, 'quantity': qty,
      'option_value_ids': optionValueIds, if (note != null) 'note': note,
    });
    return Cart.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Cart> updateItem(String uid, int qty) async {
    final data = await api.put('cart/items/$uid', body: {'quantity': qty});
    return Cart.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Cart> removeItem(String uid) async {
    final data = await api.delete('cart/items/$uid');
    return Cart.fromJson(data == null ? null : Map<String, dynamic>.from(data as Map));
  }

  Future<void> clear() => api.delete('cart');

  Future<Cart> applyPromo(String code) async {
    final data = await api.post('cart/promo', body: {'code': code});
    return Cart.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Map<String, dynamic>> quote(int addressId) async {
    final data = await api.post('cart/quote', body: {'address_id': addressId});
    return Map<String, dynamic>.from(data as Map);
  }

  Future<List<AddressDto>> addresses() async {
    final data = await api.get('addresses');
    final list = data is Map ? data['data'] : data;
    return (list as List).map((e) => AddressDto.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<AddressDto> saveAddress(AddressDto a, {int? id}) async {
    final data = id == null
        ? await api.post('addresses', body: a.toBody())
        : await api.put('addresses/$id', body: a.toBody());
    return AddressDto.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> deleteAddress(int id) => api.delete('addresses/$id');

  Future<Order> placeOrder({required int addressId, String paymentMethod = 'cash', String? note}) async {
    final data = await api.post('orders', body: {
      'address_id': addressId, 'payment_method': paymentMethod, if (note != null) 'note': note,
    });
    return Order.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<Order>> orders({bool active = false}) async {
    final data = await api.getCached('orders',
        query: {if (active) 'active': '1'}, cacheKey: active ? 'orders_active' : 'orders');
    final list = data is Map ? data['data'] : data;
    return (list as List).map((e) => Order.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<Order> order(int id) async {
    final data = await api.get('orders/$id');
    return Order.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Order> cancel(int id, {String? reason}) async {
    final data = await api.post('orders/$id/cancel', body: {if (reason != null) 'reason': reason});
    return Order.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> review(int orderId, {required int merchantRating, int? driverRating, String? comment}) =>
      api.post('orders/$orderId/review', body: {
        'merchant_rating': merchantRating,
        if (driverRating != null) 'driver_rating': driverRating,
        if (comment != null) 'comment': comment,
      });
}
