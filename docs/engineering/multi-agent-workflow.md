# Multi-Agent Workflow

Rules and protocols for coordinating multiple coding agents working on flowOS.

---

## Core Principles

1. **Narrow tasks** - Each agent receives a focused, well-defined task
2. **No overlap** - Avoid assigning overlapping files to multiple agents
3. **Architecture first** - Architecture changes complete before implementation
4. **State inspection** - Agents inspect current repo state before writing
5. **No assumptions** - Don't assume another agent's work is merged
6. **Documented handoffs** - Incomplete work must be documented
7. **Repo is authoritative** - Current repository state is the single source of truth

---

## Task Assignment Workflow

### 1. Task Decomposition
Break down features into specialized sub-tasks:

```
Feature: Inventory Management
├── Task 1: Database schema & migrations (API agent)
├── Task 2: Domain entities & repositories (API agent)
├── Task 3: Application commands/queries (API agent)
├── Task 4: HTTP endpoints & resources (API agent)
├── Task 5: OpenAPI spec update (Contract agent)
├── Task 6: Web UI components (Web agent)
├── Task 7: Mobile models & sync (Mobile agent)
├── Task 8: Desktop models & sync (Desktop agent)
└── Task 9: Integration tests (QA agent)
```

### 2. Assignment Rules
- One primary agent per sub-task
- Cross-cutting tasks (contracts, shared types) assigned to one agent
- Dependencies between tasks documented in task tracker
- Blockers communicated immediately

---

## Collaboration Patterns

### Sequential Dependency
```
Task A (API schema) → Task B (Web integration) → Task C (Mobile integration)
```
- Task B waits for Task A completion + handoff
- Task C waits for Task B completion + handoff

### Parallel Independent
```
Task A (Web feature)    Task B (Mobile feature)    Task C (Desktop feature)
```
- All can proceed simultaneously
- Shared contracts must be stable
- Sync at integration points

### Architecture-First
```
ADR Decision → Contract Update → Implementation Tasks
```
- Architecture decisions finalized before implementation
- ADR reviewed and accepted before dependent tasks start

---

## Conflict Prevention

### File Ownership
| Pattern | Rule |
|---------|------|
| Same file | Only one agent modifies at a time |
| Same directory | Coordinate via task system |
| Shared contracts | One agent owns, others consume |
| Database migrations | API agent owns, others review |

### Communication Protocol
1. **Before starting** - Check `git status` and recent commits
2. **During work** - Push commits early, communicate blockers
3. **Before finishing** - Verify no conflicts with recent changes
4. **Handoff** - Document decisions, test results, next steps

---

## Merge Strategy

### Feature Branches
- Each agent works on feature branch
- Rebase onto main before PR
- Squash merge preferred

### Integration Branch (Optional)
For large features with multiple agents:
- Create `feature/xyz-integration` branch
- Agents merge sub-tasks into integration branch
- Integration branch tested as whole
- Then merged to main

---

## Sync Points

### Daily Standup (Async)
Each agent posts:
- What was completed
- What's in progress
- Blockers needing help
- Files modified

### Weekly Architecture Sync
- Review pending ADRs
- Discuss cross-cutting concerns
- Align on technical direction

---

## Escalation

When conflicts arise:
1. **Direct communication** between agents first
2. **Task system** - Document in shared task tracker
3. **Architecture review** - If architectural disagreement
4. **Lead/maintainer** - Final decision authority

---

## Quality Gates

No agent may mark work DONE without:
- [ ] All applicable verification commands pass
- [ ] Handoff documented (if work continues)
- [ ] No uncommitted conflicts
- [ ] Tests added for new functionality
- [ ] Documentation updated if required