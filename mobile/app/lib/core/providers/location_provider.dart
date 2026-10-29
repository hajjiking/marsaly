import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';

class UserLocation {
  const UserLocation(this.lat, this.lng);
  final double lat;
  final double lng;
}

final locationProvider =
    StateNotifierProvider<LocationNotifier, UserLocation?>((ref) => LocationNotifier());

class LocationNotifier extends StateNotifier<UserLocation?> {
  LocationNotifier() : super(null) {
    refresh();
  }

  Future<void> refresh() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) return;
      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) {
        perm = await Geolocator.requestPermission();
      }
      if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) return;
      final pos = await Geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(accuracy: LocationAccuracy.medium, timeLimit: Duration(seconds: 10)));
      state = UserLocation(pos.latitude, pos.longitude);
    } catch (_) {
      // El Marsa city center fallback (mirrors backend geography config).
      state ??= const UserLocation(27.1438, -13.2056);
    }
  }
}
