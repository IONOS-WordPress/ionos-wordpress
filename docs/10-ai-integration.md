# About

This project uses AI coding assistants (agents), such as Claude Code, GitHub Copilot, and Google Gemini, to improve development workflows. Agents are AI-powered tools that read your codebase context. They help with tasks such as code generation, refactoring, testing, and commit message creation. They work inside your development environment. They read project files and run commands to help you build features faster, while they keep code quality and consistency.

The project uses one central [AGENTS.md](../AGENTS.md) file to set agent behavior and give project-specific coding guidelines. This file is the entry point for all AI agents that work on the codebase. It defines architectural patterns, coding standards, security requirements, and best practices for PHP, JavaScript, CSS, and testing. When agents read AGENTS.md, they automatically follow the project's conventions for WordPress plugin development. This includes namespace organization, template patterns, EXOS framework use, and commit message formatting.

You can change agent behavior by editing [AGENTS.md](../AGENTS.md), or the specialized documentation files it references in [docs/agent/](../docs/agent/). These documents give detailed standards for PHP syntax (PHP 8.3+ features, heredoc templating, escaping rules), JavaScript patterns (WordPress packages, async/await, EXOS framework integration), CSS architecture (native CSS nesting, BEM naming, EXOS components), security practices (input sanitization, nonce verification, capability checks), and testing approaches (PHPUnit assertions, Playwright selectors). This modular documentation structure lets you update one standard without a rewrite of the entire configuration.

The AGENTS.md approach keeps AI assistants and team members consistent. It does this by putting project knowledge into machine-readable guidelines. Instead of explaining coding patterns in chat every time, you document them once in AGENTS.md, and all agents then apply them to every code change. This reduces code review friction, prevents common mistakes such as a missing output escape or a missing nonce check, and speeds up onboarding for both human developers and AI assistants.

Beyond coding standards, AGENTS.md also sets agent workflows for complex tasks. Examples include commit message generation (using Commitizen format with plugin scopes), feature implementation (following namespace-based organization and WordPress hook patterns), and testing (running PHPUnit tests and Playwright E2E tests with proper setup). The file includes quick reference sections, common gotchas (such as the nonce function backslash exception), and links to WordPress developer resources. Together, these make it a full guide for AI-assisted development in this monorepo.

## Agent integration

The AI agent configuration in this project gives AI clients full context about the monorepo structure, development tools, and coding standards. When an AI assistant such as Claude Code, GitHub Copilot, or Google Gemini starts work in this workspace, it automatically reads [AGENTS.md](../AGENTS.md) and the referenced documentation files in [docs/agent/](../docs/agent/). It then instantly understands the pnpm workspace layout, package organization (plugins, must-use plugins, themes, Docker tools), build scripts, testing frameworks (PHPUnit, Playwright), and local development environment setup.

This configuration removes the need to explain project conventions in chat every time. The AI agent already knows to use PHP 8.3+ features, apply WordPress-specific escaping rules, use the EXOS framework for dashboard UI, follow BEM naming for CSS, use `@wordpress` packages for JavaScript, and format commit messages with Commitizen conventions. It understands architectural patterns such as namespace-based organization, heredoc templating, and hook-based extensibility. This keeps every code change aligned with the project's established practices.

Beyond general coding standards, the agent configuration provides specialized **skills** for complex workflows. Skills are pre-configured automation recipes that agents can run for tasks such as running test suites, generating features with the proper file structure, or multi-step operations. These skills use project-specific tools (wp-cli, pnpm scripts) and encode domain knowledge about the WordPress development workflow. This makes it possible to do complex tasks with simple natural-language prompts.

Skills work well for repetitive multi-step workflows that need specific tool calls and domain knowledge, such as WordPress login sequences with wp-cli, plugin activation with MCP setup, or test suite runs with proper environment teardown. They capture proven procedures that would otherwise need rediscovery or explanation each time. They turn complex operations, such as "activate plugin X, configure feature Y, verify with Z", into single-command runs. This makes skills a good fit for testing recipes, deployment procedures, environment setup tasks, and any workflow that coordinates multiple tools in a specific sequence.

### Example use cases

Whatever AI client you use, it always works in the context of the agent customization defined in [AGENTS.md].

So, answers always take the agent customization into account:

(Claude/Gemini/Copilot tested) Try these prompts in the chat:

```
What project-specific rules are currently in your context?
```

```
Are there any coding style guides or architectural constraints I've defined for this workspace?
```

#### commit message generation

Generated commit messages now also follow the commit message rules in AGENTS.md.

see docs/agent/git-conventions.md

#### generate a new feature

(claude tested) add a new essentials dashboard feature :

```
the feature should appear after the advanced section in the dashboard.
it should feature a red div element with a counter
the counter should be increased everytime the dashboard was rendered.
the increased counter should be managed in a wp_option on the serverside.
update the counter using a separate rest endpoint.
put the css in a separate css file.
additionally the counter should be shown in the javascript console using an additional ja snippet of the feature.
```

## MCP

The Model Context Protocol (MCP) gives AI agents external capabilities beyond code changes. This project configures the `chrome-devtools` MCP server, which gives AI assistants direct control over a Chrome browser instance for automated testing, visual verification, and interactive debugging. When an agent needs to test WordPress features in a real browser, check UI behavior, or work with the admin dashboard, it can use MCP to navigate pages, inspect DOM elements, run JavaScript, capture screenshots, and check functionality without manual steps.

The MCP server configuration lives in [.mcp.json](../.mcp.json) and [.vscode/mcp.json](../.vscode/mcp.json). These files set the `chrome-devtools-mcp` server to connect to a Chrome instance that runs with remote debugging enabled on port 9222. This browser integration enables test workflows that combine WordPress CLI commands (wp-cli) with browser automation. For example, you can reset the database state through the CLI, then check the changes visually in the browser. The MCP approach gives a standard protocol for agents to reach external tools. This makes capabilities such as browser control, API testing, or database inspection available across different AI clients (Claude Code, GitHub Copilot, Google Gemini).

### Usage in Development

With the MCP server configured, AI agents can run browser-based workflows through natural-language prompts. Instead of testing WordPress features by hand, you can ask the agent to do complex sequences, such as "log in to wp-admin, go to the IONOS dashboard, enable MCP support in the tools tab, and check that the WordPress MCP plugin is active." The agent then coordinates Chrome DevTools Protocol commands through MCP to automate the whole flow. It captures screenshots or console output as needed.

The MCP integration is especially useful for testing skills (see [Testing skill](#testing-skill) below), which combine WordPress environment setup through wp-cli with browser-based checks. For example, a testing recipe can reset the plugin state with wp-cli commands, then use MCP to visually confirm the reset. It does this by going to the dashboard and inspecting specific UI elements. This connects backend WordPress operations to frontend user-experience checks.

Example usage:

```
install and activate the plugins 'akisment' and 'woocommerce' in the dev container using `pnpm cli ...`

use mcp server chrome-devtools to login to http://localhost:8888 using user `admin`and the password declared in WP_PASSWORD in the project .env file and go to the woocommerce plugin settings file.
```

### Browser Setup

To use the `chrome-devtools` MCP server, launch Chrome with remote debugging enabled:

```bash
# Linux/macOS
google-chrome --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-debug
```

Once Chrome runs with remote debugging, the MCP server can connect and give browser control to AI agents. Future Chrome versions (145+) will simplify this setup with the `--auto-connect` flag, and remove the need for a manual browser launch.

## Skills

Beyond general coding standards, the agent configuration provides specialized [skills](https://agentskills.io/) for complex workflows. Skills are pre-configured automation recipes that agents can run for tasks such as running test suites, generating features with the proper file structure, or multi-step operations. These skills use project-specific tools (wp-cli, pnpm scripts) and encode domain knowledge about the WordPress development workflow. This makes it possible to do complex tasks with simple natural-language prompts.

Skills work well for repetitive multi-step workflows that need specific tool calls and domain knowledge, such as WordPress login sequences with wp-cli, plugin activation with MCP setup, or test suite runs with proper environment teardown. They capture proven procedures that would otherwise need rediscovery or explanation each time. They turn complex operations, such as "activate plugin X, configure feature Y, verify with Z", into single-command runs. This makes skills a good fit for testing recipes, deployment procedures, environment setup tasks, and any workflow that coordinates multiple tools in a specific sequence.

List known skills:

```
What skills do you know ?
```

or

```
List skills.
```

Advanced example - create a new skill :

```
create a new skill "reset essentials configuration" for resetting all wp_options created by the essentials plugin
```

### Testing skill

The [testing skill](./skills/testing/SKILL.md) provides reusable test automation recipes for common WordPress workflows. It includes pre-configured procedures and guides for test scenarios written in plain language, plus optional wp-cli commands to reset the instance. These recipes keep test runs consistent across the team, and remove the need to coordinate multiple tools by hand for complex test scenarios.

The testing skill (docs/skills/testing/SKILL.md) lets AI agents do the following:

- Discover recipes: list all available test scenarios.

  `list available /testing receipes`

- Run recipes and tests written in plain language, using Chrome DevTools MCP.

  `execute /testing skill recipe login`

  `execute /testing skill recipe activate mcp`

- Set up the environment: run WP-CLI commands before testing.
- Report results: give pass/fail status with details.

- Create a new testing recipe:

  ```
  create a new testing skill recipe `dashboard/mcp-activation.md` which should

  - login to wordpress
  - go to ionos dashboard and enable mcp
  - verify mcp is enabled by
    - testing plugin 'wordpress-mcp' is  installed and activated using wp-cli
    - testing an application password was generated

  before the recipe the following wp-cli commands should be excuted

  plugin delete wordpress-mcp
  option delete wordpress_mcp_settingsuser application-password delete 1
  ```

### STE writing skill

The [ste-writing skill](./skills/ste-writing/SKILL.md) rewrites prose (docs, READMEs, PR descriptions, error messages, release notes, comments, tool descriptions) into ASD-STE100 Simplified Technical English to remove "AI slop": short sentences, active voice, one name per concept, no marketing adjectives. It does not apply to code, identifiers, or command syntax. Vendored from [woosal1337/blog](https://github.com/woosal1337/blog/tree/main/videos/ep01-the-cure-for-ai-slop) (MIT license).

The skill has two modes:

- **strict**: procedures, runbooks, safety text, error messages
- **STE-flavored**: general prose such as READMEs and PR descriptions

Ask an agent to use it directly:

```
rewrite this PR description in STE
```

```
review docs/agent/php-standards.md for STE violations
```

A companion linter ships alongside the skill. You can run it on its own to score a draft (violations per 100 words, lower is cleaner):

```bash
python3 docs/skills/ste-writing/ste-lint.py your-draft.md            # flavored: general prose
python3 docs/skills/ste-writing/ste-lint.py --strict your-draft.md   # strict: procedures/safety text
```
