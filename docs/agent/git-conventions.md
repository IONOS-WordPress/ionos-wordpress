# Git & Commit Message Conventions

## Format

Based on [Commitizen](https://github.com/commitizen/cz-cli) / [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

[optional body]

[optional footer]
```

## Type (Required)

- **feat**: New feature
- **fix**: Bug fix
- **chore**: Maintenance, configuration, dependencies
- **docs**: Documentation changes
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **perf**: Performance improvements
- **style**: Code style changes
- **ci**: CI/CD changes
- **build**: Build system changes

## Scope (Contextual)

**Plugin changes** - Use plugin name or feature:

```bash
feat(essentials): add user profile export
fix(stretch-extra): resolve activation conflict
docs(essentials/api): document REST endpoints
```

**Monorepo changes** - Use area:

```bash
chore(deps): update WordPress packages
ci(playwright): add coverage reports
docs(agent): add commit guidelines
```

**Optional** for repository-wide changes:

```bash
chore: update dependencies
docs: improve README
```

## Subject (Required)

- Imperative, present tense: "add" not "added"
- Don't capitalize first letter
- No period at end
- Maximum 72 characters

```bash
# ✅ Good
add user authentication middleware
fix memory leak in dashboard component
update dependencies to latest versions

# ❌ Bad
Added user authentication  # Wrong tense
Fix Memory Leak           # Capitalized
updated dependencies.     # Period + wrong tense
```

## Body (Optional)

- Explain **what** and **why**, not **how**
- Wrap at 72 characters
- Separate from subject with blank line

## Footer (Optional)

```bash
feat(essentials): migrate to new API

BREAKING CHANGE: The old /v1/users endpoint is no longer supported.
Use /v2/users instead.

Closes #456
Refs #123
```

## Examples

```bash
# Plugin features
feat(essentials): add two-factor authentication
fix(stretch-extra/dashboard): correct CSS alignment

# Monorepo
chore(deps): update @wordpress/scripts to 27.0.0
docs(agent): enhance testing documentation

# Breaking change
feat(essentials): migrate settings API

BREAKING CHANGE: Old settings not automatically migrated.

# Co-authoring
feat(essentials): add export functionality

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

## Branch Naming

- **Main**: `develop`
- **Feature**: `feat/plugin-name/feature-name`
- **Fix**: `fix/plugin-name/bug-description`
- **Chore**: `chore/task-description`

## Pull Requests

- Title: Same format as commit messages
- Description: Context, testing, related issues
- Reference: Link issues and PRs

---

**See Also**: [PHP Standards](php-standards.md), [JavaScript Standards](javascript-standards.md)
