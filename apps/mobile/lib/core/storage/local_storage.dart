import 'package:shared_preferences/shared_preferences.dart';

class LocalStorage {
  static SharedPreferences? _prefs;

  static Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
  }

  static Future<bool> setString(String key, String value) async {
    return await _prefs?.setString(key, value) ?? false;
  }

  static String? getString(String key) {
    return _prefs?.getString(key);
  }

  static Future<bool> setBool(String key, bool value) async {
    return await _prefs?.setBool(key, value) ?? false;
  }

  static bool getBool(String key, {bool defaultValue = false}) {
    return _prefs?.getBool(key) ?? defaultValue;
  }

  static Future<bool> setInt(String key, int value) async {
    return await _prefs?.setInt(key, value) ?? false;
  }

  static int getInt(String key, {int defaultValue = 0}) {
    return _prefs?.getInt(key) ?? defaultValue;
  }

  static Future<bool> setDouble(String key, double value) async {
    return await _prefs?.setDouble(key, value) ?? false;
  }

  static double getDouble(String key, {double defaultValue = 0.0}) {
    return _prefs?.getDouble(key) ?? defaultValue;
  }

  static Future<bool> remove(String key) async {
    return await _prefs?.remove(key) ?? false;
  }

  static Future<bool> clear() async {
    return await _prefs?.clear() ?? false;
  }

  // App-specific keys
  static const String keyAuthToken = 'auth_token';
  static const String keyCurrentOrgId = 'current_org_id';
  static const String keyCurrentBranchId = 'current_branch_id';
  static const String keyUserId = 'user_id';
  static const String keyIsOfflineMode = 'is_offline_mode';
  static const String keyLastSyncAt = 'last_sync_at';
  static const String keyThemeMode = 'theme_mode';
}
