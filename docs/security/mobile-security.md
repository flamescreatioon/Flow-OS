# Mobile Security

No durable backend, payment, database, or master secret belongs in a compiled app. Authentication is user/device to backend, not embedded credentials. Production tokens must use OS-backed secure storage (for example `flutter_secure_storage` and Keychain/Keystore); `SharedPreferences` is unacceptable for authentication tokens.

The mobile prototype stores the authentication token with `flutter_secure_storage`; SharedPreferences retains non-secret UI/sync metadata only (selected IDs, offline mode, last-sync timestamp, and theme). The Dio interceptor reads the secure token for a Bearer request and debug logs only method, path, and status—never headers, bodies, cookies, passwords, or tokens. SQLite is not encrypted by default. Future design must assess device encryption, app-level database encryption where justified, and secure key handling without prematurely choosing a cross-platform encryption library. Production APIs require TLS.
