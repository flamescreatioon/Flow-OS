# Naming Conventions

Names communicate responsibility and intent. Prefer `organizationMembership`, `resolveTenant`, and `isActive` to vague abbreviations, `process`, or `statusFlag`. Widely understood terms such as API, URL, ID, DTO, and SKU are acceptable.

Use verbs for functions, nouns for types, and readable `is`/`has`/`can` prefixes for booleans. Avoid dumping grounds named `Helper`, `Utils`, `Common`, or `Manager` unless the narrow responsibility is genuinely clear. Do not append `Service` automatically. Use DTOs to make meaningful boundaries explicit, not for every trivial call.

Follow language idioms: PHP and C# classes are PascalCase; TypeScript variables/functions are camelCase; Dart follows standard Dart conventions; database and external API fields are snake_case. Keep external contract names stable and map explicitly at boundaries when internal names differ. Use enums for closed, meaningful state, and use named constants/configuration where a literal's business meaning would otherwise be unclear.
