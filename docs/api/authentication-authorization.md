# Authentication and Authorization

Sanctum is the authentication foundation. 401 means no valid identity; 403 means authenticated but forbidden. Authorization remains server-side through policies/gates/application logic; UI hiding is never security. Sensitive model fields and token/password hashes are never serialized.
