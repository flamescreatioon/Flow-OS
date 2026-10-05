import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Stores only authentication material in the platform keystore/keychain.
/// SharedPreferences is intentionally not used for credentials.
class SecureTokenStorage {
  static const String _authTokenKey = 'auth_token';
  static const FlutterSecureStorage _storage = FlutterSecureStorage();

  static Future<void> writeAuthToken(String token) =>
      _storage.write(key: _authTokenKey, value: token);

  static Future<String?> readAuthToken() async =>
      _storage.read(key: _authTokenKey);

  static Future<void> deleteAuthToken() async =>
      _storage.delete(key: _authTokenKey);
}
