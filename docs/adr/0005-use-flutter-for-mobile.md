# ADR 0005: Use Flutter for Mobile

## Title
Use Flutter for Mobile Application

## Status
Accepted

## Context
Need a cross-platform mobile framework for iOS and Android with:
- Single codebase
- Native performance
- Offline capability
- Good developer experience
- Strong ecosystem

## Decision
Use **Flutter 3+ with Dart 3+** and:
- Riverpod for state management
- Dio for networking
- sqflite for local database
- Freezed for immutable models
- go_router for navigation

## Consequences

### Positive
- **Single Codebase**: iOS, Android, Web, Desktop from one codebase
- **Performance**: Compiled to ARM, 60/120fps
- **Hot Reload**: Sub-second iteration
- **Widgets**: Everything is a widget, consistent UI
- **Null Safety**: Sound null safety in Dart 3
- **Offline-First**: sqflite + custom sync engine
- **Ecosystem**: Growing package ecosystem

### Negative
- **Dart Language**: Less common than JS/TS
- **App Size**: Larger than native (~5-10MB overhead)
- **Platform APIs**: Some require platform channels
- **Web Support**: Not as mature as native web

### Mitigations
- Invest in Dart training
- Use `flutter build apk --split-per-abi` for size
- Platform channels for native features
- Focus on mobile, web as secondary

## Alternatives Considered

### React Native
- **Pros**: JavaScript/TypeScript, huge ecosystem, Expo
- **Cons**: Bridge overhead, Metro bundler issues, less consistent UI

### Native (Swift/Kotlin)
- **Pros**: Best performance, platform APIs
- **Cons**: Two codebases, higher cost, slower iteration

### Ionic/Capacitor
- **Pros**: Web tech, single codebase
- **Cons**: WebView performance, limited native access

### Kotlin Multiplatform (KMP)
- **Pros**: Share business logic, native UI
- **Cons**: Immature, iOS UI still SwiftUI, less ecosystem

## Offline Architecture

```dart
// Local-first with sqflite
class DatabaseHelper {
  static Database? _database;

  Future<Database> get database async {
    // SQLite with sync_queue table
  }
}

// Background sync with workmanager
Workmanager().registerPeriodicTask('sync', 'periodicSync');
```

## References
- [Flutter](https://flutter.dev/)
- [Riverpod](https://riverpod.dev/)
- [Freezed](https://pub.dev/packages/freezed)
