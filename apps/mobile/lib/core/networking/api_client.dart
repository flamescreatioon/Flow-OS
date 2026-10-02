import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../config/app_config.dart';

class ApiClient {
  late final Dio _dio;

  ApiClient() {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.apiBaseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          if (kDebugMode) {
            print('REQUEST[${options.method}] => PATH: ${options.path}');
          }
          return handler.next(options);
        },
        onResponse: (response, handler) {
          if (kDebugMode) {
            print(
                'RESPONSE[${response.statusCode}] => PATH: ${response.requestOptions.path}');
          }
          return handler.next(response);
        },
        onError: (error, handler) {
          if (kDebugMode) {
            print(
                'ERROR[${error.response?.statusCode}] => PATH: ${error.requestOptions.path}');
          }
          return handler.next(error);
        },
      ),
    );
  }

  Dio get dio => _dio;

  Future<dynamic> get(String path,
      {Map<String, dynamic>? queryParameters}) async {
    final response = await _dio.get(path, queryParameters: queryParameters);
    return response.data;
  }

  Future<dynamic> post(String path, {dynamic data}) async {
    final response = await _dio.post(path, data: data);
    return response.data;
  }

  Future<dynamic> put(String path, {dynamic data}) async {
    final response = await _dio.put(path, data: data);
    return response.data;
  }

  Future<dynamic> patch(String path, {dynamic data}) async {
    final response = await _dio.patch(path, data: data);
    return response.data;
  }

  Future<dynamic> delete(String path) async {
    final response = await _dio.delete(path);
    return response.data;
  }
}

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());
