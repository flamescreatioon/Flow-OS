# Agent Git Rules

Rules and guardrails for Git usage by AI coding agents (such as Antigravity, Codex Pro, and OpenCode) working in the **flowOS** repository.

---

## 1. Core Principles

1. **Pre-Change Inspection**: Always inspect the repository state before writing or modifying code:
   ```bash
   git status
   git diff
   git log --oneline -5
   ```
2. **Preserve User Changes**: Never overwrite, discard, or reset uncommitted work left by the user. If uncommitted work is present, scope changes strictly away from those files.
3. **No Unrelated Modifications**: Avoid modifying unrelated files or opportunistic refactoring unless explicitly requested.
4. **Never Blindly Stage**: Avoid blanket `git add .`. Inspect unstaged and untracked files before staging. Deliberately exclude secrets, environment files, build artifacts, and logs.
5. **No Force Pushes or Destructive Git Operations**: Agents must never run `git push --force`, `git reset --hard` on shared branches, `git clean -fd`, or branch deletions without explicit instruction.

---

## 2. Agent Commit & Push Authority

- **Local Changes & Staging**: Agents are authorized to inspect, edit, and stage task-scoped files.
- **Commit Authority**: Agents may create local git commits following Conventional Commits **only when explicitly tasked or requested** by the user.
- **Push & Merge Restrictions**: Agents must **NOT** push commits to remote repositories (`git push`) or execute pull request merges unless explicitly authorized by the user. Remote publishing remains under human oversight.

---

## 3. Branching Model

All branches branch from `main`:

```bash
git checkout main
git pull origin main
git checkout -b feature/short-description
```

Branch naming prefixes:
- `feature/` — Functional additions
- `fix/` — Defect corrections
- `chore/` — Tooling, dependencies, repository maintenance
- `docs/` — Documentation updates
- `refactor/` — Code refactoring without behavior modification

---

## 4. Conventional Commit Standards

Every commit created by an agent must follow Conventional Commits:

```text
<type>(<scope>): <imperative description>
```

- **Types**: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`, `build`, `ci`, `revert`
- **Scopes**: `api`, `web`, `mobile`, `desktop`, `contracts`, `types`, `infra`, `docs`, `repo`
- **Examples**:
  - `feat(api): add organization membership endpoint`
  - `fix(mobile): resolve offline queue retry backoff`
  - `docs(testing): update integration test guidance`

---

## 5. Multi-Agent Collision Avoidance

When multiple agents (Antigravity, Codex Pro, OpenCode) collaborate simultaneously:

1. **File Partitioning**: Sub-tasks must never assign overlapping files to multiple agents at the same time.
2. **Subsystem Isolation**: Distribute tasks along architectural boundaries (`apps/api`, `apps/web`, `apps/mobile`, `apps/desktop`, `packages/contracts`).
3. **Rebase Before Handoff**: If working on a branch while another agent merged changes, rebase against latest `main`:
   ```bash
   git fetch origin
   git rebase origin/main
   ```
4. **Semantic Conflict Resolution**: Never resolve merge conflicts by arbitrarily choosing one side (`ours` or `theirs`). Review semantics carefully and re-run all tests.
5. **Optional Git Worktrees**: For parallel agent work on distinct feature branches, Git worktrees can be used to prevent workspace collisions:
   ```bash
   git worktree add ../flowos-agent-api feature/api-sync
   ```

---

## 6. Pre-Handoff Quality Gate

Before completing an assignment or handing off to another agent, verify:
- [ ] `git status` shows only expected, scoped modifications
- [ ] No secrets, keys, or `.env` files are tracked or staged
- [ ] No binary or build artifacts are tracked
- [ ] Relevant test commands executed and reported accurately
- [ ] Documented handoff note created if work continues