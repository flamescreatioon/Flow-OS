# Temporal Types

An **instant** is a global moment; a **calendar date** is `YYYY-MM-DD`; a **local time** is a wall-clock `HH:MM:SS`; a local date-time requires separate timezone semantics; a zoned future event preserves IANA zone; a duration is explicit units, never a fake date. `*_at` names instants, `*_date` dates, and `*_time` local times.
