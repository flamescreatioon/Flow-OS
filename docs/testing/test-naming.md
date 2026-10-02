# Test Naming

Names describe behavior and condition, not implementation or sequence. Prefer `user_without_membership_cannot_establish_tenant`, `duplicate_sync_operation_is_idempotent`, and `negative_money_string_parses_exactly` over `test1` or `works`.

Use idiomatic conventions: descriptive PHP test methods, Dart `test`/`testWidgets` descriptions, Jest `describe`/`test` behavior sentences, and C# `Method_Condition_ExpectedResult` or equivalent clear xUnit names. Arrange/Act/Assert is useful when it clarifies setup; comments are not mandatory when the test already reads clearly.
