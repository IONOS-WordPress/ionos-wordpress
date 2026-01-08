# IONOS WordPress Monorepo - AI Agent Coding Guidelines

## Overview

This document serves as the entry point for AI agents working on the IONOS WordPress monorepo. It provides high-level project context and references to detailed coding standards.

## Project Structure

This is a WordPress monorepo using pnpm workspaces containing plugins, must-use plugins, themes, and development tools.

```
/
├── packages/
│   ├── wp-plugin/           WordPress plugins
│   ├── wp-mu-plugin/        Must-use plugins
│   ├── wp-theme/            WordPress themes
│   ├── docker/              Containerized development tools
│   ├── docs/                Documentation
│   └── npm/                 NPM packages
├── phpunit/                 PHP unit test configuration
├── playwright/              E2E test configuration
├── scripts/                 Build and automation scripts
└── docs/agent/              **AI Agent Documentation**
```

## Documentation Structure

All coding standards are organized in `/docs/agent/`:

### Core Standards

- **[PHP Standards](docs/agent/php-standards.md)** - PHP 8.3+, modern syntax, templating, formatting
- **[JavaScript Standards](docs/agent/javascript-standards.md)** - ES6+, WordPress packages, async/await
- **[CSS Architecture](docs/agent/css-architecture.md)** - Native CSS, BEM, modern features
- **[Security Standards](docs/agent/security.md)** - Input sanitization, output escaping, nonces

### Testing

- **[PHPUnit Testing](docs/agent/phpunit-testing.md)** - WordPress unit tests, assertions, patterns
- **[E2E Testing](docs/agent/e2e-testing.md)** - Playwright, WordPress E2E utils, selectors

### Integration

- **[WordPress Integration](docs/agent/wordpress-integration.md)** - Hooks, APIs, admin pages, database

### Workflow

- **[Git Conventions](docs/agent/git-conventions.md)** - Commitizen format, commit messages, branching

## Quick Reference

**Important**: Every PHP / JS / CSS file should contain a header describing its contents.

### Critical Rules

**PHP**:

- `\` prefix for WordPress functions (e.g., `\add_action()`) **EXCEPT** `check_admin_referer()` and `check_ajax_referer()`
- Heredoc-printf for HTML (avoid `<?php ?>` tag switching)
- Late escaping: escape at output, not before storage
- Short array syntax: `[]` not `array()`
- No Yoda conditions: `$var === 'value'` not `'value' === $var` (literal on right)
- Named parameters for non-standard PHP functions with 3+ parameters (skip parameters with default values)
- Combine multiple `isset()` checks: `isset($a, $b, $c)` not `isset($a) && isset($b)`
- Modern array functions: `array_find()`, `array_any()`, `array_all()` (PHP 8.4+)
- **Arrow functions `fn() =>` for single expressions, anonymous `function() {}` for multi-line**
- Inline/anonymous functions if only used once, named functions when reused
- Functions over classes when possible
- See [PHP Standards](docs/agent/php-standards.md) for details

**JavaScript**:

- Use `@wordpress/dom-ready` not `DOMContentLoaded`
- Use `@wordpress/api-fetch` for WordPress REST API
- Use `@wordpress/i18n` for all translations
- Async/await over Promise chains
- **Dashboard (Essentials plugin)**: Use EXOS framework (`window.EXOS`) for React UI components
- See [JavaScript Standards](docs/agent/javascript-standards.md) for details

**CSS**:

- **Prefer EXOS CSS framework** (`https://ce1.uicdn.net/exos/framework/3.0/exos.min.css`) for dashboard/admin UI components
- Native CSS nesting (not SCSS)
- CSS Variables (not SCSS variables)
- BEM naming: `.block__element--modifier`
- Mobile-first responsive design
- See [CSS Architecture](docs/agent/css-architecture.md) for details

**Security** (see [Security Standards](docs/agent/security.md)):

- [ ] Input: Sanitize (`\sanitize_text_field()`, `absint()`)
- [ ] Output: Escape (`\esc_html()`, `\esc_attr()`, `\esc_url()`)
- [ ] State changes: Verify nonce (`check_admin_referer()`)
- [ ] Permissions: Check capability (`\current_user_can()`)
- [ ] Database: Use `$wpdb->prepare()` always

**Testing** (see [PHPUnit](docs/agent/phpunit-testing.md) & [E2E](docs/agent/e2e-testing.md)):

- PHPUnit: Extend `\WP_UnitTestCase`, use Arrange-Act-Assert
- Playwright: Use `@wordpress/e2e-test-utils-playwright`, tag tests appropriately

## Requirements

- **PHP**: 8.3+
- **WordPress**: 6.6+
- **Node**: Latest LTS
- **Package Manager**: pnpm

## Key Architectural Patterns

1. **Namespace-based Organization** - Functions in namespaces, not classes
2. **Hook-based Extensibility** - WordPress actions and filters
3. **Function-first Approach** - Prefer functions over classes
4. **Heredoc Templating** - Clean HTML output without tag switching
5. **Late Escaping** - Escape at output, not before
6. **Shadow DOM** - Dashboard uses Shadow DOM for isolation
7. **EXOS Framework** - Dashboard UI uses EXOS React components and CSS
8. **Monorepo Structure** - Shared tooling and centralized standards

## Development Workflow

### Running Tests

```bash
# PHP unit tests
pnpm test:php

# E2E tests
pnpm test:e2e

# Linting
pnpm lint
pnpm lint-fix
```

### Building

```bash
# Build all packages
pnpm build

# Watch mode
pnpm watch
```

### Local Environment

```bash
# Start WordPress environment
pnpm start

# Stop environment
pnpm stop
```

## Code Formatting

- **Indentation**: 2 spaces (all languages)
- **Line Length**: 120 characters maximum
- **Line Endings**: Unix-style (LF)
- **Trailing Whitespace**: Remove all
- **Final Newline**: Always include

## File Conventions

### PHP Files

```php
<?php

namespace vendor\plugin\feature;

defined('ABSPATH') || exit();

// Imports
use function vendor\plugin\_helper;
use const vendor\plugin\CONSTANT;

// Constants
const FEATURE_OPTION = 'option_name';

// Functions
function initialize(): void {
  \add_action('init', __NAMESPACE__ . '\setup');
}
```

### JavaScript Files

```javascript
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

domReady(() => {
  // Initialization
});
```

### Test Files

- **PHPUnit**: `*Test.php` in `tests/phpunit/`
- **Playwright**: `*.spec.js` in `tests/e2e/`

## Common Gotchas

1. **Nonce Functions**: NO backslash for `check_admin_referer()` and `check_ajax_referer()`
2. **Tag Switching**: Avoid `<?php ?>` tag switching - use heredoc pattern
3. **Early Escaping**: Don't escape before storage - escape at output
4. **DOM Ready**: Use `@wordpress/dom-ready` not native `DOMContentLoaded`
5. **API Fetch**: Use `@wordpress/api-fetch` not raw `fetch()` for WordPress REST
6. **Prepared Statements**: Always use `$wpdb->prepare()` for SQL
7. **Late Binding**: Functions referenced in hooks use `__NAMESPACE__ . '\function_name'`
8. **Commit Messages**: Use Commitizen format with plugin scope: `feat(plugin): description`

## Getting Help

- **Questions about standards**: Reference `/docs/agent/` documentation
- **Plugin-specific patterns**: Check existing code in the same plugin
- **WordPress functions**: Reference [WordPress Developer Resources](https://developer.wordpress.org/)

## Version Control

- **Main Branch**: `develop`
- **Commit Format**: [Commitizen](https://github.com/commitizen/cz-cli) standard (Conventional Commits)
- **Commit Scope**: Include plugin/feature name for plugin changes (e.g., `feat(essentials): description`)
- **Pull Requests**: Reference related issues, provide clear description

See [Git Conventions](docs/agent/git-conventions.md) for detailed commit message format and examples.

---

## Next Steps

When starting work on this repository:

1. **Read relevant standards** in `/docs/agent/`
2. **Review existing code** in the feature area you'll modify
3. **Run tests** to ensure environment is working
4. **Follow patterns** established in the codebase

For detailed information on any topic, refer to the specific documentation files in `/docs/agent/`.
