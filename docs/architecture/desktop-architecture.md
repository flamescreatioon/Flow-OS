# Desktop Architecture

The desktop client is a .NET 8 WPF application with the following solution projects:

- `FlowOS.Desktop`
- `FlowOS.Domain`
- `FlowOS.Application`
- `FlowOS.Infrastructure`
- `FlowOS.Sync`

The intended dependency direction is presentation/infrastructure toward application and domain. The desktop is offline-first in direction; EF Core SQLite supports local data, while sync and device lifecycle remain foundation work rather than implemented product capabilities.

`Microsoft.Extensions.Logging` is the logging abstraction. Persistent log files are not enabled by default. Future desktop credentials must use Windows Credential Manager or DPAPI-protected storage; no production token implementation is claimed here.

See [offline sync architecture](offline-sync-architecture.md), [desktop security](../security/desktop-security.md), and [desktop observability](../observability/desktop-observability.md).
