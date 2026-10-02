import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/networking/api_client.dart';
import '../../core/storage/local_storage.dart';
import '../../core/storage/secure_token_storage.dart';
import '../../core/errors/app_error.dart';

class AuthService {
  final ApiClient _apiClient;

  AuthService(this._apiClient);

  Future<void> login({
    required String email,
    required String password,
    required String organizationSlug,
  }) async {
    try {
      final response = await _apiClient.post('/auth/login', data: {
        'email': email,
        'password': password,
        'organization_slug': organizationSlug,
      });

      final token = response['token'] as String?;
      final user = response['user'] as Map<String, dynamic>?;

      if (token == null || user == null) {
        throw AuthError('Invalid response from server');
      }

      await SecureTokenStorage.writeAuthToken(token);
      await LocalStorage.setString(
          LocalStorage.keyUserId, user['id'] as String);
      await LocalStorage.setString(
          LocalStorage.keyCurrentOrgId, user['organization_id'] as String);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        throw AuthError('Invalid credentials');
      }
      throw NetworkError('Login failed: ${e.message}');
    }
  }

  Future<void> logout() async {
    await SecureTokenStorage.deleteAuthToken();
    await LocalStorage.remove(LocalStorage.keyUserId);
    await LocalStorage.remove(LocalStorage.keyCurrentOrgId);
    await LocalStorage.remove(LocalStorage.keyCurrentBranchId);
  }

  Future<String?> getAuthToken() {
    return SecureTokenStorage.readAuthToken();
  }

  String? getCurrentOrgId() {
    return LocalStorage.getString(LocalStorage.keyCurrentOrgId);
  }

  String? getCurrentBranchId() {
    return LocalStorage.getString(LocalStorage.keyCurrentBranchId);
  }

  Future<bool> get isAuthenticated async => (await getAuthToken()) != null;
}

final authServiceProvider = Provider<AuthService>((ref) {
  return AuthService(ref.read(apiClientProvider));
});

final authStateProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.read(authServiceProvider));
});

class AuthState {
  final bool isAuthenticated;
  final bool isLoading;
  final String? error;

  const AuthState({
    this.isAuthenticated = false,
    this.isLoading = false,
    this.error,
  });

  AuthState copyWith({
    bool? isAuthenticated,
    bool? isLoading,
    String? error,
  }) {
    return AuthState(
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      error: error ?? this.error,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthService _authService;

  AuthNotifier(this._authService) : super(const AuthState()) {
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    final token = await _authService.getAuthToken();
    state = state.copyWith(isAuthenticated: token != null);
  }

  Future<void> login({
    required String email,
    required String password,
    required String organizationSlug,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      await _authService.login(
        email: email,
        password: password,
        organizationSlug: organizationSlug,
      );
      state = state.copyWith(isAuthenticated: true, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
        isAuthenticated: false,
      );
      rethrow;
    }
  }

  Future<void> logout() async {
    await _authService.logout();
    state = state.copyWith(isAuthenticated: false);
  }
}
