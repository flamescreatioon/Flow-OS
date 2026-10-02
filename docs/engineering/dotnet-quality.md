# .NET and C# Quality

All current desktop projects target .NET 8 and enable nullable reference types. Keep nullable enabled and fix warnings at their source; do not globally suppress them. Run `dotnet build FlowOS.sln`, `dotnet test FlowOS.sln`, and `dotnet format FlowOS.sln --verify-no-changes` for relevant desktop work.

Use idiomatic PascalCase public symbols and camelCase locals/parameters, explicit domain types where useful, constructor injection, and correct disposal/lifetime ownership. Return `Task` or `Task<T>` from asynchronous work; `async void` is limited to required event handlers, and `.Result`/`.Wait()` require exceptional justification.

Respect the existing dependency direction: Domain -> Application -> Infrastructure -> Sync -> Desktop. WPF code-behind remains presentation-only; use the existing MVVM support rather than introducing another UI pattern. Use `decimal` only for calculations when necessary and integer minor units for authoritative money; never `float`/`double`.
