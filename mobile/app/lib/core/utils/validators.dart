/// Phone validation mirrors backend config: starts with 6 or 7, total 10
/// digits when prefixed with the +212 country code.
class Validators {
  static String? phone(String cc, String v) {
    final cleaned = v.replaceAll(RegExp(r'[\s-]'), '');
    final local = cleaned.startsWith(cc) ? cleaned.substring(cc.length) : cleaned;
    if (!RegExp(r'^[67]\d{8}$').hasMatch(local)) return 'invalid_phone';
    return null;
  }

  static String normalizedPhone(String cc, String v) {
    final cleaned = v.replaceAll(RegExp(r'[\s-]'), '');
    final local = cleaned.startsWith(cc) ? cleaned.substring(cc.length) : cleaned;
    return '$cc$local';
  }

  static String? required(String? v) =>
      (v == null || v.trim().isEmpty) ? 'required' : null;
}
