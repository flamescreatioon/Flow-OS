# Client Date/Time

TypeScript treats API instants/dates as distinct documented ISO strings and uses `Intl.DateTimeFormat` for display. Dart instants use explicit UTC (`toUtc`); core DateTime does not retain arbitrary IANA zones. C# uses `DateTimeOffset` for instants, `DateOnly` dates, and `TimeOnly` local times. SQLite stores canonical UTC ISO instants and date-only strings.
