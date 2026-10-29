import '../models/dto.dart';

/// Public catalog endpoints with offline caching keys.
class CatalogRepository {
  CatalogRepository({required this.api});
  final dynamic api;

  Future<List<Category>> categories() async {
    final data = await api.getCached('categories', cacheKey: 'categories');
    return (data as List).map((e) => Category.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<List<Merchant>> merchants({int? page, int? categoryId, String? search,
      bool openNow = false, double minRating = 0, double maxFee = 0, double? lat, double? lng}) async {
    final q = <String, dynamic>{
      if (page != null) 'page': page,
      if (categoryId != null) 'category_id': categoryId,
      if (search != null && search.isNotEmpty) 'search': search,
      if (openNow) 'open_now': '1',
      if (minRating > 0) 'min_rating': minRating,
      if (maxFee > 0) 'max_delivery_fee': maxFee,
      if (lat != null) 'lat': lat,
      if (lng != null) 'lng': lng,
    };
    final data = await api.getCached('merchants', query: q, cacheKey: 'merchants');
    final list = data is Map ? data['data'] : data;
    return (list as List).map((e) => Merchant.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<Merchant> merchant(int id, {double? lat, double? lng}) async {
    final data = await api.getCached('merchants/$id',
        query: {if (lat != null) 'lat': lat, if (lng != null) 'lng': lng},
        cacheKey: 'merchant_$id');
    return Merchant.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Product> product(int id) async {
    final data = await api.get('products/$id');
    return Product.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<Banner>> banners() async {
    final data = await api.getCached('banners', cacheKey: 'banners');
    return (data as List).map((e) => Banner.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<List<Announcement>> announcements() async {
    final data = await api.getCached('announcements', cacheKey: 'announcements');
    return (data as List).map((e) => Announcement.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }
}
