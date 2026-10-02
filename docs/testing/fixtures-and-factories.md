# Fixtures and Factories

Use small, explicit, purpose-built data. Laravel factories should create valid baseline entities; tests override only values relevant to the behavior. Use builders only when complex object setup repeats meaningfully. Do not hide important tenant/authorization conditions behind opaque helpers.

Use stable explicit UUIDs when identity itself matters, otherwise assert behavior rather than random values. Never use production dumps, customer data, real credentials, real email/phone recipients, or developer directories. File tests use temporary/fake storage; email tests use framework fakes rather than Mailpit.
