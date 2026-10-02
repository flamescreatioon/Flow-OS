# Design Tokens Package

Shared design tokens for flowOS to ensure visual consistency across all platforms.

## Purpose

Design tokens are the atomic values of the design system — the raw values that represent visual design decisions. They serve as the single source of truth for:

- Colors
- Spacing
- Typography
- Border radius
- Shadows
- Transitions
- Breakpoints
- Z-index layers

## Token Source and Transformation Pipeline

```
tokens/base.json          # Canonical, platform-neutral canonical source
        ↓
Transformation Pipeline (Style Dictionary / custom scripts)
        ↓
Platform-specific outputs
    ├── TypeScript  → @flowos/design-tokens package (src/index.ts)
    ├── Dart/Flutter  → lib/design_tokens.dart
    └── C#/WPF        → DesignTokens.cs (generated via T4/Source Generator)
```

### Source of Truth

**`tokens/base.json`** is the canonical, platform-neutral canonical token source. All platform-specific outputs are derived from this file.

### Current Platform Artifacts

| Platform | Artifact | Status |
|----------|----------|--------|
| TypeScript (Web) | `packages/design-tokens/src/index.ts` | **Transitional** — manually maintained, to be replaced by generated output from `tokens/base.json` |
| Dart/Flutter | `lib/design_tokens.dart` | Not yet implemented |
| C#/WPF | `DesignTokens.cs` | Not yet implemented |

### TypeScript `index.ts` Status

The file `packages/design-tokens/src/index.ts` is currently **transitional** — it is manually maintained and duplicates the token values from the canonical `tokens/base.json`. It will be replaced by generated output once the transformation pipeline (Style Dictionary or equivalent) is implemented.

**Do not** add new tokens to `index.ts` directly. Add them to `tokens/base.json` instead.

## Philosophy

Design tokens should be:

- **Platform-neutral** — Raw values that can be transformed into TypeScript, Dart, C#, PHP
- **Semantic** — Named by purpose (e.g., `primary.500`) not appearance (e.g., `blue.500`)
- **Transformable** — Compatible with tools like Style Dictionary, Tokens Studio
- **Semantic where appropriate** — Semantic aliases (e.g., `color.status.success`) map to base tokens

## Token Categories

### Colors
- Primary brand scale (50-950)
- Secondary/success scale
- Warning scale
- Error scale
- Neutral grays (0-950)
- Semantic mappings (text, border, status, background)

### Spacing
- Consistent 4px base unit scale (0-24)

### Typography
- Font families (sans, mono)
- Font sizes (xs-5xl)
- Font weights (normal-bold)
- Line heights

### Border Radius
- none, sm, md, lg, xl, 2xl, full

### Shadows
- none, sm, md, lg, xl, inner

### Transitions
- fast, normal, slow

### Breakpoints
- sm, md, lg, xl, 2xl

### Z-Index
- Semantic layering (hide, base, dropdown, sticky, modal, popover, tooltip, toast)

## Token Format

Tokens in `tokens/base.json` follow the Style Dictionary / Design Token Community Group format:

```json
{
  "color": {
    "primary": {
      "500": { "value": "#3B82F6", "type": "color" }
    }
  },
  "spacing": {
    "4": { "value": "1rem", "type": "dimension" }
  }
}
```

## Transformation Pipeline (Future)

When tooling is established:

1. `tokens/base.json` → Style Dictionary → Platform-specific outputs
2. TypeScript: `@flowos/design-tokens` package (generated, replaces `src/index.ts`)
3. Dart: Generated `lib/design_tokens.dart`
4. C#: Generated `DesignTokens.cs` via T4/Source Generator
5. PHP: Constants in `config/design_tokens.php`

## Adding New Tokens

1. Add to `tokens/base.json` following existing structure
2. Run transformation pipeline (when available)
3. Update platform-specific packages
4. Update documentation

## What NOT to Put Here

- Component-specific styles (those belong in components)
- Platform-specific overrides
- Runtime-computed values
- Legacy/deprecated values without deprecation path