# Changeset Workflow

## Overview

This project uses [`@changesets/cli`](https://github.com/changesets/changesets) to manage versioning and changelogs across the monorepo. **Whenever you implement a new feature, bug fix, or breaking change, you must create a changeset file** in `.changeset/` before the work is considered complete.

## When to Create a Changeset

Create a changeset for:

- **New features** (`minor` bump)
- **Bug fixes** (`patch` bump)
- **Breaking changes** (`major` bump)
- **Significant refactors** that affect public behavior (`patch` or `minor`)

Do **not** create a changeset for:

- Documentation-only changes (`docs`)
- CI/CD changes (`ci`)
- Internal tooling / dev-dependency updates (`chore`)
- Code style / formatting (`style`)

## Changeset File Format

A changeset is a markdown file with YAML frontmatter:

```md
---
'@ionos-wordpress/essentials': minor
---

add user profile export feature
```

### Fields

| Field | Description |
|---|---|
| Package name | The `name` from the affected package's `package.json` (with quotes) |
| Bump type | `patch`, `minor`, or `major` (see rules below) |
| Body | One-line imperative description matching the commit message subject |

### Bump Type Rules

| Type | When to use |
|---|---|
| `patch` | Bug fix, small tweak, non-visible refactor |
| `minor` | New backward-compatible feature or enhancement |
| `major` | Breaking change: removed API, changed behavior, incompatible upgrade |

### Multiple Packages

If a change spans multiple packages, list each on a separate line:

```md
---
'@ionos-wordpress/essentials': minor
'@ionos-wordpress/stretch-extra': patch
---

add shared maintenance mode API
```

## Package Names

Packages in this monorepo and their names as used in changesets:

| Package directory | Changeset name |
|---|---|
| `packages/wp-plugin/essentials/` | `@ionos-wordpress/essentials` |
| `packages/wp-mu-plugin/stretch-extra/` | `@ionos-wordpress/stretch-extra` |
| `packages/npm/ecs-php/` | `@ionos-wordpress/ecs-php` |
| `packages/npm/dennis-i18n/` | `@ionos-wordpress/dennis-i18n` |
| `packages/npm/potrans/` | `@ionos-wordpress/potrans` |
| `packages/npm/rector-php/` | `@ionos-wordpress/rector-php` |
| `packages/docker/wpdev-caddy/` | `@ionos-wordpress/wpdev-caddy` |

When in doubt, check the `name` field in the relevant `package.json`.

## Filename Convention

Use a short, descriptive kebab-case filename that summarises the change:

```
.changeset/add-maintenance-mode-thumbnails.md
.changeset/fix-activation-conflict.md
.changeset/extend-hosting-data.md
```

Avoid generic names like `fix.md` or `change.md`. The filename is permanent and appears in git history.

## Agent Workflow: Creating a Changeset

Follow these steps every time a feature or fix is implemented:

### Step 1 — Extract information from user input

Before asking the user anything, infer as much as possible from the conversation:

- **Affected package**: Which package directory does the changed code live in?
- **Bump type**: Is this a new feature (`minor`), a bug fix (`patch`), or a breaking change (`major`)?
- **Description**: What did the feature/fix do? Use the commit subject as the basis.

### Step 2 — Ask for missing information interactively

Only ask for information you could not determine from context. Ask one focused question at a time. Common gaps:

- Bump type when the change is ambiguous
- Whether multiple packages are affected
- Clarification on the description wording

Example questions:
- "Should this be a `patch` (bug fix) or `minor` (new feature) release bump?"
- "Does this change also affect `@ionos-wordpress/stretch-extra`, or only `@ionos-wordpress/essentials`?"

### Step 3 — Present the changeset for review

Before writing the file, show the user the full changeset content and the proposed filename:

```
Changeset to be created at .changeset/add-apcu-feature.md:

---
'@ionos-wordpress/essentials': patch
---

add apcu feature
```

Wait for explicit confirmation before writing the file.

### Step 4 — Write the file

Once confirmed, create the file in `.changeset/`:

```bash
# Example path
.changeset/add-apcu-feature.md
```

Do not run `changeset add` via CLI — write the file directly.

## Examples

### Feature addition

User asks to add a new export feature to the Essentials plugin:

```md
---
'@ionos-wordpress/essentials': minor
---

add user profile export feature
```

File: `.changeset/add-user-profile-export.md`

### Bug fix

User reports a CSS alignment bug in stretch-extra:

```md
---
'@ionos-wordpress/stretch-extra': patch
---

fix CSS alignment in maintenance mode overlay
```

File: `.changeset/fix-maintenance-overlay-alignment.md`

### Breaking change

API endpoint removed from Essentials:

```md
---
'@ionos-wordpress/essentials': major
---

remove deprecated v1 settings API endpoint
```

File: `.changeset/remove-v1-settings-api.md`

---

**See Also**: [Git Conventions](git-conventions.md), [WordPress Integration](wordpress-integration.md)
