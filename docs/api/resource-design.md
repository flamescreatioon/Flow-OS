# Resource Design

Use plural nouns and HTTP semantics: GET reads, POST creates/commands, PUT replaces where appropriate, PATCH partially updates, DELETE requests the domain's deletion/archive behavior. Nest only useful relationship context; use direct IDs after that. Domain commands may use `POST /resources/{id}/action` when CRUD is unsuitable.
