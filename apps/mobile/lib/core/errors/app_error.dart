abstract class AppError implements Exception {
  final String message;
  final String? code;
  final dynamic originalError;

  const AppError(this.message, {this.code, this.originalError});

  @override
  String toString() =>
      'AppError: $message${code != null ? ' (code: $code)' : ''}';
}

class NetworkError extends AppError {
  const NetworkError(super.message, {super.code, super.originalError});
}

class AuthError extends AppError {
  const AuthError(super.message, {super.code, super.originalError});
}

class ValidationError extends AppError {
  final Map<String, List<String>>? fieldErrors;

  const ValidationError(super.message,
      {this.fieldErrors, super.code, super.originalError});
}

class NotFoundError extends AppError {
  const NotFoundError(super.message, {super.code, super.originalError});
}

class ConflictError extends AppError {
  const ConflictError(super.message, {super.code, super.originalError});
}

class OfflineError extends AppError {
  const OfflineError(super.message, {super.code, super.originalError});
}

class SyncError extends AppError {
  const SyncError(super.message, {super.code, super.originalError});
}

class DatabaseError extends AppError {
  const DatabaseError(super.message, {super.code, super.originalError});
}

class UnknownError extends AppError {
  const UnknownError(super.message, {super.code, super.originalError});
}
