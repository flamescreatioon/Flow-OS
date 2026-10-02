# Database Date/Time

Use `TIMESTAMPTZ` for instants, `DATE` for calendar dates, and deliberate `TIME WITHOUT TIME ZONE` for local times. Use half-open query ranges (`>= start`, `< end`) after calculating local organization boundaries and converting them to UTC. Do not use timestamp-only sync cursors.
