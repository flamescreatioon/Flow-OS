# Desktop Testing

`FlowOS.Domain.Tests`, `FlowOS.Application.Tests`, and `FlowOS.Infrastructure.Tests` are all included in `FlowOS.sln` and correctly reference `Microsoft.NET.Test.Sdk` plus the xUnit adapter. Their zero-test discovery is intentional after Sprint 0.19 removes scaffold-only assertions; it is not a configuration failure.

Future domain tests remain infrastructure-free. Application tests exercise use-case orchestration through narrow ports/fakes. Infrastructure tests use the smallest suitable substitute (SQLite, PostgreSQL, filesystem temporary storage, or HTTP fakes) and reserve real PostgreSQL behavior for integration testing. WPF GUI automation is deferred; ViewModels, application, and domain code must stay testable without it.
