import 'package:flowos_mobile/core/config/app_config.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('AppConfig', () {
    test('provides the default tenant-facing locale configuration', () {
      expect(AppConfig.appName, 'flowOS');
      expect(AppConfig.defaultCurrency, 'NGN');
      expect(AppConfig.defaultTimezone, 'Africa/Lagos');
    });
  });
}
