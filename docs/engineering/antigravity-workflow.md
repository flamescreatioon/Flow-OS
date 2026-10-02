# Antigravity Workflow

Guidelines for using Antigravity with the flowOS repository.

---

## Configuration Status

As of this writing, Antigravity does not have a native repository-level configuration file mechanism (like `.codex/` for Codex or `.opencode/` for OpenCode).

This document serves as the reference for how Antigravity should operate within the flowOS repository.

---

## Primary Instruction Source

Antigravity must follow the same source of truth as other agents:

1. **Root `AGENTS.md`** - Primary agent instructions
2. **`docs/engineering/`** - Detailed engineering rules
3. **`docs/adr/`** - Architecture Decision Records
4. **`docs/architecture/`** - System architecture documentation

---

## Antigravity-Specific Behavior

### Context Loading
When starting work, Antigravity should:
1. Read `AGENTS.md` completely
2. Read relevant `docs/engineering/*.md` files for the task
3. Inspect current repository state (`git status`, recent commits)
4. Review any existing handoff notes

### Tool Usage
- Use standard file operations (read, write, edit)
- Use bash for commands
- Use git for version control operations
- Follow the same verification commands as other agents

### Output Format
Antigravity should use the standard completion report format:

```
Created
Modified
Verified
Issues
Remaining Work
```

---

## Unsupported Configurations

Do not create:
- `.antigravity/` directory (no native support)
- `antigravity.json` or similar (no specification exists)
- Custom instruction files outside the documented system

If Antigravity adds native configuration support in the future, this document should be updated to reflect the actual mechanism.

---

## Verification for Antigravity

Antigravity should run the same verification commands as other agents:

| Application | Commands |
|-------------|----------|
| Web | `npm run lint`, `npm run typecheck`, `npm run test`, `npm run build` |
| API | `composer lint`, `php artisan test` |
| Mobile | `flutter analyze`, `flutter test` |
| Desktop | `dotnet format --verify-no-changes`, `dotnet test`, `dotnet build` |

---

## Handoff Compliance

Antigravity must follow the same handoff protocol as other agents:
- Document decisions, test results, and next steps
- Create handoff notes when work is incomplete or blocked
- Read and respect handoff notes from previous agents

---

## Updates

This document should be updated when:
- Antigravity adds native configuration support
- New agent-specific workflows are established
- Integration patterns change