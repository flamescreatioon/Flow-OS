import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flowos_mobile/app/app.dart';

void main() {
  testWidgets('FlowOS app shows home screen with flowOS title',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(child: FlowOSApp()),
    );

    expect(find.text('flowOS'), findsAtLeastNWidgets(1));
    expect(find.text('Business Operating System for Nigerian Businesses'),
        findsOneWidget);
  });
}
