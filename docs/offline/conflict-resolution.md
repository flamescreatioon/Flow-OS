# Conflict Resolution

A mutable operation carries `base_version`, meaning the version the client edited. If the server holds another version, it returns a conflict rather than silently overwriting data. `updated_at` and client clocks are audit context, not concurrency tokens.

Resolution is selected by domain: server-wins for server-controlled data; narrowly scoped client-wins only where approved; field merge for independent simple fields; and explicit human/domain resolution otherwise. Last-writer-wins is not a universal rule.

Transactional domains require domain semantics: inventory should converge through movements rather than overwriting a quantity; finance uses reversal, adjustment, or compensating entries rather than destructive overwrite. A conflict requiring attention retains the local attempt, server value, and reason for later UX.
