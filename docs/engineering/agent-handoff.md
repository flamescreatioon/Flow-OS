# Agent Handoff System

Defines the protocol and structure for handoffs between coding agents (Antigravity, Codex Pro, OpenCode) in the **flowOS** repository.

---

## 1. Handoff Template

Every agent handoff note must include the following fields:

```markdown
## Task
[Original task description and assigned scope]

## Status
[TODO | IN PROGRESS | BLOCKED | DONE]

## Git State
- **Branch**: `main` or `feature/...`
- **Working Tree**: [clean | uncommitted changes present | staged]
- **Commit Hash**: [commit hash if committed, or N/A if not yet committed]

## Files Changed
- `path/to/file1.ts` — [created | modified | deleted] — [brief reason]
- `path/to/file2.php` — [created | modified | deleted] — [brief reason]

## Important Decisions
- [Decision 1]: [Rationale and architecture alignment]
- [Decision 2]: [Rationale and architecture alignment]

## Tests Run & Factual Results
- Unit tests: `command` — [passed | failed | test count]
- Integration tests: `command` — [passed | failed]
- Static analysis / linter: `command` — [passed | failed]

## Known Issues & Pre-Existing Debt
- [Issue 1]: [Description, impact, and whether pre-existing or new deferred debt]
- [Issue 2]: [Description, impact]

## Next Recommended Action
[Specific, actionable next step for the receiving agent]

## Context for Next Agent
[Any additional nuances, environment requirements, or non-obvious details]
```

---

## 2. When to Create a Handoff

1. **Task Completion**: When marking an assigned stage DONE and transitioning to a subsequent stage.
2. **Blockers Encountered**: When progress is BLOCKED on external dependencies, permissions, or prerequisite ADRs.
3. **Session / Turn Limits**: When an agent completes its turn or hits context/turn thresholds before completing a large task.
4. **Subsystem Handoff**: When a task moves across agent specializations (e.g. backend API contract complete → frontend Web integration).

---

## 3. Storage Location

Handoffs should be recorded in:
- The completion summary or PR description for immediate visibility
- `docs/engineering/handoffs/` for multi-day, complex initiatives across agents

---

## 4. Quality Standards

| Standard | Compliant | Prohibited |
|----------|-----------|------------|
| **Status** | Specific state (`DONE`, `BLOCKED`) with evidence | Vague statements (`"almost done"`, `"works"`) |
| **Git Tracking** | Branch name, working tree cleanliness, exact commit hash | Omitting Git state or claiming clean tree without checking |
| **Test Verification** | Exact command with passed test counts | Generic `"all tests pass"` without running them |
| **Known Issues** | Disclosing known pre-existing findings transparently | Concealing failures or ignoring lint errors |
| **Files Modified** | Granular list with specific paths and rationale | Missing files or blanket descriptions |