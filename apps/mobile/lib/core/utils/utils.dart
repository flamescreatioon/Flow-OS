import 'package:intl/intl.dart';

class DateUtils {
  static const String defaultDateFormat = 'yyyy-MM-dd';
  static const String defaultDateTimeFormat = 'yyyy-MM-dd HH:mm:ss';
  static const String apiDateTimeFormat = 'yyyy-MM-dd\'T\'HH:mm:ss.SSS\'Z\'';

  static String formatDate(DateTime date, {String format = defaultDateFormat}) {
    return DateFormat(format).format(date);
  }

  static String formatDateTime(DateTime dateTime,
      {String format = defaultDateTimeFormat}) {
    return DateFormat(format).format(dateTime);
  }

  static DateTime? parseDateTime(String? value) {
    if (value == null || value.isEmpty) return null;
    try {
      return DateTime.parse(value);
    } catch (_) {
      return null;
    }
  }

  static String toApiFormat(DateTime dateTime) {
    return dateTime.toUtc().toIso8601String();
  }
}

class CurrencyUtils {
  static const String defaultCurrency = 'NGN';
  static const String defaultLocale = 'en_NG';

  static String formatAmount(int amountInMinorUnits,
      {String currency = defaultCurrency, String locale = defaultLocale}) {
    final formatter = NumberFormat.currency(
      locale: locale,
      symbol: _getCurrencySymbol(currency),
      decimalDigits: _getDecimalDigits(currency),
    );
    return formatter
        .format(amountInMinorUnits / _getMinorUnitDivisor(currency));
  }

  static int toMinorUnits(double amount, {String currency = defaultCurrency}) {
    return (amount * _getMinorUnitDivisor(currency)).round();
  }

  static double fromMinorUnits(int amountInMinorUnits,
      {String currency = defaultCurrency}) {
    return amountInMinorUnits / _getMinorUnitDivisor(currency);
  }

  static int _getMinorUnitDivisor(String currency) {
    switch (currency) {
      case 'NGN':
      case 'USD':
      case 'EUR':
      case 'GBP':
        return 100;
      case 'JPY':
      case 'KRW':
        return 1;
      default:
        return 100;
    }
  }

  static String _getCurrencySymbol(String currency) {
    switch (currency) {
      case 'NGN':
        return '₦';
      case 'USD':
        return '\$';
      case 'EUR':
        return '€';
      case 'GBP':
        return '£';
      case 'JPY':
        return '¥';
      default:
        return currency;
    }
  }

  static int _getDecimalDigits(String currency) {
    switch (currency) {
      case 'JPY':
      case 'KRW':
        return 0;
      default:
        return 2;
    }
  }
}

class StringUtils {
  static String capitalize(String value) {
    if (value.isEmpty) return value;
    return value[0].toUpperCase() + value.substring(1).toLowerCase();
  }

  static String slugify(String value) {
    return value
        .toLowerCase()
        .replaceAll(RegExp(r'[^a-z0-9]+'), '-')
        .replaceAll(RegExp(r'^-+|-+$'), '');
  }

  static String truncate(String value, int maxLength, {String suffix = '...'}) {
    if (value.length <= maxLength) return value;
    return '${value.substring(0, maxLength - suffix.length)}$suffix';
  }
}
