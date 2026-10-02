import 'package:flutter/material.dart';
import 'package:flowos_mobile/core/config/app_config.dart';

class FlowOSApp extends StatelessWidget {
  const FlowOSApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: AppConfig.appName,
      theme: AppConfig.lightTheme,
      darkTheme: AppConfig.darkTheme,
      themeMode: ThemeMode.system,
      home: const FlowOSHomeScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class FlowOSHomeScreen extends StatelessWidget {
  const FlowOSHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(AppConfig.appName),
      ),
      body: const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.business,
              size: 64,
              color: Colors.blue,
            ),
            const SizedBox(height: 16),
            Text(
              AppConfig.appName,
              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'Business Operating System for Nigerian Businesses',
              style: TextStyle(fontSize: 16, color: Colors.grey),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            const Text(
              'Flutter + Riverpod + Dio + SQLite',
              style: TextStyle(fontSize: 14, color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }
}
