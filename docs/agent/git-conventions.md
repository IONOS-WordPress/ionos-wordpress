# Git & Commit Message Conventions

## Commit Message Format

This repository follows the **[Commitizen](https://github.com/commitizen/cz-cli)** standard for commit messages, based on the [Conventional Commits](https://www.conventionalcommits.org/) specification.

### Format Structure

```
<type>(<scope>): <subject>

[optional body]

[optional footer]
```

### Components

#### Type (Required)

The type of change being committed:

- **feat**: New feature
- **fix**: Bug fix
- **chore**: Maintenance tasks, configuration, dependencies
- **docs**: Documentation changes
- **refactor**: Code refactoring (no functional changes)
- **test**: Adding or updating tests
- **perf**: Performance improvements
- **style**: Code style changes (formatting, whitespace)
- **ci**: CI/CD pipeline changes
- **build**: Build system or external dependency changes
- **revert**: Reverting a previous commit

#### Scope (Contextual)

The scope identifies the plugin, feature, or area affected by the change.

**Plugin Changes** - Use plugin name or plugin feature as scope:

```bash
feat(essentials): add user profile export functionality
fix(stretch-extra): resolve plugin activation conflict
chore(pre-release): update build configuration
docs(essentials/api): document REST API endpoints
refactor(stretch-extra/dashboard): simplify settings UI
test(essentials/auth): add login flow tests
```

**Monorepo Changes** - Use area or package as scope:

```bash
chore(deps): update WordPress packages
ci(playwright): add E2E test coverage reports
build(docker): update PHP version to 8.3
docs(agent): add commit message guidelines
```

**Scope is optional** for changes affecting the entire repository:

```bash
chore: update node dependencies
docs: improve README documentation
```

#### Subject (Required)

The subject contains a succinct description of the change:

- Use imperative, present tense: "add" not "added" or "adds"
- Don't capitalize the first letter
- No period (.) at the end
- Maximum 72 characters

**Good Examples:**
```
add user authentication middleware
fix memory leak in dashboard component
update dependencies to latest versions
remove deprecated API endpoints
```

**Bad Examples:**
```
Added user authentication middleware  # Wrong tense
Fix Memory Leak In Dashboard Component  # Capitalized
updated dependencies.  # Wrong tense and has period
Fixes #123  # Use footer for issue references
```

#### Body (Optional)

The body provides additional context about the change:

- Use imperative, present tense
- Wrap at 72 characters per line
- Explain **what** and **why**, not **how**
- Separate from subject with a blank line

```
feat(essentials): add two-factor authentication

Implement TOTP-based two-factor authentication for enhanced
security. Users can enable 2FA in their profile settings.

This addresses security concerns raised in the Q4 audit.
```

#### Footer (Optional)

The footer contains metadata about the commit:

- **Breaking Changes**: Start with `BREAKING CHANGE:` followed by description
- **Issue References**: `Closes #123`, `Fixes #456`, `Refs #789`
- **Co-authored-by**: Credit co-authors

```
feat(essentials): migrate to new API endpoint

BREAKING CHANGE: The old /v1/users endpoint is no longer supported.
Use /v2/users instead.

Closes #456
Refs #123
```

## Plugin-Specific Guidelines

### Changes to WordPress Plugins

When modifying code in a WordPress plugin, **always include the plugin name or feature** as the scope:

```bash
# Plugin name as scope
feat(essentials): add custom post type for resources
fix(stretch-extra): resolve admin notice display issue
chore(pre-release): update version number

# Plugin feature as scope (for larger plugins)
feat(essentials/dashboard): add analytics widget
fix(stretch-extra/plugins): handle missing directory gracefully
refactor(essentials/api): simplify endpoint registration
```

### Plugin Structure Reference

Common plugin scopes in this repository:

- `essentials` - IONOS Essentials plugin
- `stretch-extra` - IONOS Stretch Extra plugin (mu-plugin)
- `pre-release` - Pre-release plugin
- `assistant` - IONOS Assistant plugin

### Feature Scopes

For plugin-specific features, use `plugin/feature` format:

```bash
feat(essentials/auth): implement OAuth2 support
fix(stretch-extra/dashboard): correct CSS alignment
test(essentials/api): add REST endpoint coverage
```

## Common Patterns

### Multiple Related Changes

If changes span multiple areas, choose the primary scope or omit:

```bash
# Primary scope
feat(essentials): add user management system

# No scope (affects multiple areas equally)
chore: update coding standards across all plugins
```

### Dependency Updates

```bash
chore(deps): update @wordpress/scripts to 27.0.0
chore: update node dependencies
chore(docker): update PHP image to 8.3
```

### Breaking Changes

Always document breaking changes in the footer:

```bash
feat(essentials): migrate to new settings API

BREAKING CHANGE: Old settings stored in wp_options are not
automatically migrated. Users must re-configure their settings.

Migration guide: docs/migration/v2.md
```

### Issue References

Reference issues in the footer, not the subject:

```bash
# ✅ Correct
fix(stretch-extra): resolve plugin activation conflict

Closes #456

# ❌ Incorrect
fix(stretch-extra): resolve plugin activation conflict (#456)
```

## Co-Authoring Commits

When AI assists with commits, include co-authorship:

```bash
feat(essentials): add user profile export

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

## Examples from Repository

Real examples from this codebase:

```bash
# Plugin features
feat(stretch-extra): add e2e tests for secondary plugin directory functionality
fix(stretch-extra): update testMatch pattern for e2e tests to include mu plugins
fix(essentials): fixed linting rules

# Monorepo maintenance
chore: enhance PHPUnit testing documentation for agents
chore: initial agents.md including php conventions
chore: update node dependencies
chore: applied updated php ecs rules to plugins

# Plugin-specific maintenance
chore(stretch-extra): fixed missing admin referrer checks
chore(pre-release): remove interactive flag from sbom generation command

# Build/CI
updated ecs, potrans and rector-php docker images

# Documentation
docs(agent): add commit message guidelines
```

## Git Workflow

### Branch Naming

- **Main Branch**: `develop`
- **Feature Branches**: `feat/feature-name` or `feat/plugin-name/feature-name`
- **Fix Branches**: `fix/bug-description` or `fix/plugin-name/bug-description`
- **Chore Branches**: `chore/task-description`

Examples:
```bash
feat/essentials/two-factor-auth
fix/stretch-extra/activation-conflict
chore/update-dependencies
docs/agent-guidelines
```

### Commit Workflow

1. **Stage Changes**: `git add <files>`
2. **Commit with Message**: Follow format above
3. **Push to Remote**: `git push origin <branch-name>`

### Pull Requests

- **Title**: Use same format as commit messages
- **Description**: Provide context, testing instructions, related issues
- **Reference**: Link related issues and PRs

Example PR title:
```
feat(essentials): add two-factor authentication
```

## Linting Commit Messages

The repository may use commit message linting tools. Ensure your commits follow the format to pass CI checks.

### Common Validation Rules

- Type must be valid (feat, fix, chore, etc.)
- Subject must not exceed 72 characters
- Subject must be lowercase (after type/scope)
- Subject must not end with a period

## Quick Reference

```bash
# Feature with plugin scope
feat(essentials): add feature description

# Bug fix with plugin feature scope
fix(stretch-extra/dashboard): fix bug description

# Maintenance task
chore(deps): update dependencies

# Documentation
docs(agent): update guidelines

# Refactoring with plugin scope
refactor(essentials/api): simplify implementation

# Test addition
test(stretch-extra): add test coverage
```

## Resources

- [Commitizen](https://github.com/commitizen/cz-cli)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Angular Commit Guidelines](https://github.com/angular/angular/blob/main/CONTRIBUTING.md#commit)

---

**See Also**:
- [PHP Standards](php-standards.md)
- [JavaScript Standards](javascript-standards.md)
- [PHPUnit Testing](phpunit-testing.md)
