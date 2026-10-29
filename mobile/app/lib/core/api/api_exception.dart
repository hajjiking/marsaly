/// Typed failure surfaced to the UI from the Marsaly API envelope
/// {success:false, message, errors:{field:[msgs]}}.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.fieldErrors = const {}});

  final String message;
  final int? statusCode;
  final Map<String, List<String>> fieldErrors;

  bool get isAuth => statusCode == 401;
  bool get isNetwork => statusCode == null;

  @override
  String toString() => 'ApiException($statusCode): $message';
}
