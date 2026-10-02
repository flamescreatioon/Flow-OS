/// Application routing configuration.
/// Currently uses Flutter's built-in navigation. Replace with go_router or similar when needed.
import 'package:flutter/material.dart';

class AppRouter {
  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case '/':
        return MaterialPageRoute(builder: (_) => const FlowOSHomeScreen());
      default:
        return MaterialPageRoute(
          builder: (_) => const Scaffold(
            body: Center(child: Text('Page not found')),
          ),
        );
    }
  }
}

/// Placeholder for the home screen widget reference.
/// Actual widget is defined in app.dart
class FlowOSHomeScreen extends StatelessWidget {
  const FlowOSHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(child: Text('FlowOS Home')),
    );
  }
}
