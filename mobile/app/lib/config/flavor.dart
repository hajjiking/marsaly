/// App flavors: dev vs prod. Selected at compile time with
/// `--dart-define=FLAVOR=dev|prod`.
enum Flavor {
  dev('Marsaly Dev', 'http://10.0.2.2:8000/api/v1'), // Android emulator → host Laravel
  prod('Marsaly', 'https://api.marsaly.ma/api/v1');

  const Flavor(this.appName, this.baseUrl);
  final String appName;
  final String baseUrl;

  static Flavor get current {
    const f = String.fromEnvironment('FLAVOR', defaultValue: 'dev');
    return f == 'prod' ? Flavor.prod : Flavor.dev;
  }
}

/// Central brand theme values — mirrors backend config/marsaly.php so the
/// branding can be changed in one place per platform. Overridden at runtime
/// by GET /config when the backend is reachable.
class BrandTheme {
  static const String defaultAppName = 'Marsaly';
  static const String defaultAppNameAr = 'مرسالي';
  static const String defaultTaglineEn = 'Everything, delivered to your door';
  static const String defaultTaglineFr = 'Tout, livré chez vous';
  static const String defaultTaglineAr = 'كلشي لحد باب دارك';

  // Warm orange primary + teal accent (placeholder until real branding).
  static const int primary = 0xFFF97316;
  static const int secondary = 0xFF0D9488;
  static const int dark = 0xFF1C1917;
  static const int light = 0xFFFFF7ED;
}
