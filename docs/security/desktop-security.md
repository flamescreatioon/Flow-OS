# Desktop Security

Desktop binaries cannot safely hold durable backend or payment secrets. User/device tokens belong in Windows Credential Manager, DPAPI-protected storage, or an approved equivalent—not `appsettings.json`, plaintext SQLite, or user-readable configuration.

The current WPF scaffold has console/debug logging and no credential, HTTP, or SQLite implementation. Future logging must redact secrets and production transport must use TLS. Offline SQLite is not encrypted by default; device compromise can expose data, so OS device encryption, database encryption options, and protected keys require a platform-specific decision before sensitive offline data is stored.
