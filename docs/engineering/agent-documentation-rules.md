# Agent Documentation Rules

Rules for maintaining documentation consistency with code changes.

---

## When Documentation Must Be Updated

Documentation updates are required when changes affect:

| Area | Documentation Location |
|------|------------------------|
| Architecture decisions | `docs/architecture/`, new ADR in `docs/adr/` |
| API endpoints | `docs/api/`, OpenAPI spec in `packages/contracts/openapi/` |
| Database conventions | `docs/database/`, `docs/engineering/agent-database-rules.md` |
| Offline strategy | `docs/offline/`, `docs/engineering/agent-database-rules.md` |
| Security practices | `docs/security/`, `docs/engineering/agent-dependency-policy.md` |
| Development setup | `docs/engineering/development-setup.md` |
| Major dependencies | `docs/engineering/dependency-policy.md`, ADR if significant |
| Coding standards | `docs/engineering/coding-standards.md` |
| Git workflow | `docs/engineering/git-workflow.md` |
| Testing strategy | `docs/testing/README.md` |
| Agent rules | `AGENTS.md`, `docs/engineering/` agent-*.md files |

---

## Documentation Update Process

1. **Identify affected docs** during planning phase
2. **Update docs in same PR** as code changes
3. **Follow existing style** and formatting
3. **Keep concise** - documentation should be actionable, not verbose
4. **Link related docs** using relative paths
5. **Update table of contents** if structure changes

---

## ADR Requirements

Create an ADR when making decisions involving:
- New framework or major library
- New database or data store
- New application architecture pattern
- Sync strategy changes
- Tenancy model changes
- Authentication architecture changes
- API paradigm changes (REST → GraphQL, etc.)
- New infrastructure platform (Kubernetes, cloud provider)

### ADR Format
```markdown
# ADR NNNN: Short Title

## Status
Accepted | Proposed | Superseded

## Context
What is the issue? What forces are at play?

## Decision
What are we doing?

## Consequences
What are the trade-offs? What becomes easier/harder?
```

---

## Prohibited Documentation Practices

- **Do not** update documentation for changes that did not actually occur
- **Do not** create documentation for planned/future features as if they exist
- **Do not** duplicate content across multiple files - link instead
- **Do not** leave outdated examples that no longer work
- **Do not** use absolute paths that break when repo moves

---

## Documentation Quality Standards

| Standard | Requirement |
|----------|-------------|
| Accuracy | Code examples must be runnable and current |
| Brevity | Prefer bullet points over long paragraphs |
| Discoverability | Clear headings, table of contents for long docs |
| Link integrity | All internal links must resolve |
| Version relevance | Note when docs apply to specific versions |

---

## Review Checklist

Before merging documentation changes:
- [ ] All code examples are syntactically correct
- [ ] Internal links resolve (no 404s)
- [ ] No references to deleted/moved files
- [ ] Style matches existing docs in same directory
- [ ] ADR created if architectural decision
- [ ] OpenAPI spec updated if API changed
- [ ] Changelog updated if user-facing
