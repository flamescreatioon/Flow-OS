# Task State System

Defines the task lifecycle states and completion criteria for flowOS development.

---

## Task States

```text
TODO → IN PROGRESS → BLOCKED → DONE
         ↓              ↑
         └──────────────┘
```

### TODO
- Task identified and documented
- Not yet assigned or started
- May have dependencies on other tasks
- Acceptance criteria defined

### IN PROGRESS
- Agent actively working on task
- Changes being made to repository
- Regular commits expected
- Status updates in task system

### BLOCKED
- Task cannot proceed due to external dependency
- Blocker clearly documented with:
  - What is blocking
  - Who/what is needed to unblock
  - Estimated unblock time (if known)
- Agent should work on other tasks while blocked

### DONE
**All of the following must be true:**
1. ✅ Requested implementation completed
2. ✅ Appropriate verification performed
3. ✅ Failures reported (none hidden)
4. ✅ Documentation updated where needed
5. ✅ Handoff created if work continues

**DONE does NOT mean:**
- Files were generated
- Code compiles
- "It works on my machine"
- Tests pass but weren't run by agent

---

## State Transitions

| From | To | Trigger |
|------|-----|---------|
| TODO | IN PROGRESS | Agent begins work |
| IN PROGRESS | BLOCKED | Blocker encountered |
| BLOCKED | IN PROGRESS | Blocker resolved |
| IN PROGRESS | DONE | All DONE criteria met |
| IN PROGRESS | TODO | Work abandoned/cancelled |
| DONE | IN PROGRESS | Reopened (regression found) |

---

## DONE Criteria Checklist

Before marking any task DONE:

### Implementation
- [ ] All requested functionality implemented
- [ ] No TODOs or FIXMEs in new code (or tracked as separate tasks)
- [ ] Code follows project coding standards
- [ ] No commented-out code or debug statements

### Verification
- [ ] All applicable lint/format checks pass
- [ ] All applicable type checks pass
- [ ] All applicable unit tests pass
- [ ] All applicable integration tests pass
- [ ] Build succeeds
- [ ] Application boots (if applicable)

### Testing
- [ ] New functionality covered by tests
- [ ] Edge cases tested
- [ ] Error paths tested
- [ ] Offline scenarios tested (if applicable)

### Documentation
- [ ] Code comments for complex logic
- [ ] README/docs updated if user-facing
- [ ] ADR created if architectural decision
- [ ] OpenAPI spec updated if API changed
- [ ] Changelog updated if user-facing

### Git Hygiene
- [ ] Clean commit history (squashed fixups)
- [ ] No merge conflicts
- [ ] Branch follows naming convention
- [ ] Commit messages follow conventional format

### Handoff (if applicable)
- [ ] Handoff document created
- [ ] Next steps clearly identified
- [ ] Known issues documented

---

## Task Tracking

Tasks tracked in:
- GitHub Issues (primary)
- PR descriptions (for implementation tasks)
- `docs/engineering/handoffs/` (for complex multi-agent tasks)

### Task Template
```markdown
## Task: [Brief title]

**State:** TODO | IN PROGRESS | BLOCKED | DONE

**Assignee:** [Agent name/identifier]

**Description:** [What needs to be done]

**Acceptance Criteria:**
- [ ] Criterion 1
- [ ] Criterion 2

**Dependencies:** [Task IDs or "None"]

**Files Likely Involved:**
- `path/to/file1`
- `path/to/file2`

**Verification Commands:**
```bash
cd apps/web && npm run test
cd apps/api && php artisan test
```

**Notes:** [Any additional context]
```

---

## Anti-Patterns

| Anti-Pattern | Why It's Bad | Correction |
|--------------|--------------|------------|
| Marking DONE without verification | Hidden failures | Run all verification commands |
| "Almost done" = DONE | No clear completion | Use explicit DONE checklist |
| Skipping tests for speed | Regressions | Tests are mandatory |
| No handoff on incomplete work | Next agent lost | Always document handoff |
| Moving BLOCKED to TODO without resolution | Lost context | Keep BLOCKED until resolved |