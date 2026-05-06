# About

This project integrates AI coding assistants (agents) like Claude Code, GitHub Copilot, and Google Gemini to enhance development workflows. Agents are AI-powered tools that understand your codebase context and assist with tasks like code generation, refactoring, testing, and commit message creation. They operate within your development environment, reading project files and executing commands to help you build features faster while maintaining code quality and consistency.

The project uses a centralized [AGENTS.md](../AGENTS.md) file to customize agent behavior and provide project-specific coding guidelines. This file serves as the entry point for all AI agents working on the codebase, defining architectural patterns, coding standards, security requirements, and best practices across PHP, JavaScript, CSS, and testing. When agents read AGENTS.md, they automatically follow the project's conventions for WordPress plugin development, including namespace organization, template patterns, EXOS framework usage, and commit message formatting.

You can customize agent behavior by modifying [AGENTS.md](../AGENTS.md) or the specialized documentation files it references in [docs/agent/](../docs/agent/). These documents include detailed standards for PHP syntax (PHP 8.3+ features, heredoc templating, escaping rules), JavaScript patterns (WordPress packages, async/await, EXOS framework integration), CSS architecture (native CSS nesting, BEM naming, EXOS components), security practices (input sanitization, nonce verification, capability checks), and testing approaches (PHPUnit assertions, Playwright selectors). The modular documentation structure allows you to update specific standards without rewriting the entire configuration.

The AGENTS.md approach ensures consistency across different AI assistants and team members by codifying project knowledge into machine-readable guidelines. Instead of repeatedly explaining coding patterns in chat, you document them once in AGENTS.md, and all agents automatically apply them to every code change. This dramatically reduces code review friction, prevents common mistakes like forgetting to escape output or missing nonce verification, and accelerates onboarding for both human developers and AI assistants.

Beyond coding standards, AGENTS.md also configures agent workflows for complex tasks like commit message generation (using Commitizen format with plugin scopes), feature implementation (following namespace-based organization and WordPress hook patterns), and testing (executing PHPUnit tests and Playwright E2E tests with proper setup). The file includes quick reference sections, common gotchas (like the nonce function backslash exception), and links to WordPress developer resources, making it a comprehensive guide for AI-assisted development in this monorepo.

## Agent integration

The AI agent configuration in this project provides comprehensive context to AI clients about the monorepo structure, development tools, and coding standards. When an AI assistant like Claude Code, GitHub Copilot, or Google Gemini starts working in this workspace, it automatically reads [AGENTS.md](../AGENTS.md) and the referenced documentation files in [docs/agent/](../docs/agent/), gaining instant understanding of the pnpm workspace layout, package organization (plugins, must-use plugins, themes, Docker tools), build scripts, testing frameworks (PHPUnit, Playwright), and local development environment setup.

This configuration eliminates the need to repeatedly explain project conventions in chat. The AI agent already knows to use PHP 8.3+ features, apply WordPress-specific escaping rules, leverage the EXOS framework for dashboard UI, follow BEM naming for CSS, use `@wordpress` packages for JavaScript, and format commit messages with Commitizen conventions. It understands architectural patterns like namespace-based organization, heredoc templating, and hook-based extensibility, ensuring every code change aligns with the project's established practices.

Beyond general coding standards, the agent configuration provides specialized **skills** for complex workflows. Skills are pre-configured automation recipes that agents can execute for tasks like running test suites, generating features with proper file structure, or performing multi-step operations. These skills leverage project-specific tools (wp-cli, pnpm scripts) and encode domain knowledge about the WordPress development workflow, making it possible to accomplish sophisticated tasks with simple natural language prompts.

Skills are particularly good for repetitive multi-step workflows that require specific tool invocations and domain knowledge, such as WordPress login sequences with wp-cli, plugin activation with MCP setup, or test suite execution with proper environment teardown. They capture proven procedures that would otherwise need to be rediscovered or explained each time, turning complex operations like "activate plugin X, configure feature Y, verify with Z" into single-command executions. This makes skills ideal for testing recipes, deployment procedures, environment setup tasks, and any workflow that involves coordinating multiple tools in a specific sequence.

### Example use cases

Whatever AI client you use - they are always in context of the agent customization defined in [AGENTS.md].

That's why answers will always take the agent customization into account :

(Claude/Gemini/Copilot tested) Try these prompts in the chat:

```
What project-specific rules are currently in your context?
```

```
Are there any coding style guides or architectural constraints I've defined for this workspace?
```

#### commit message generation

Generated commit messages will now also honor commit messages rules in AGENTS.md.

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

The Model Context Protocol (MCP) extends AI agents with external capabilities beyond code manipulation. This project configures the `chrome-devtools` MCP server, which gives AI assistants direct control over a Chrome browser instance for automated testing, visual verification, and interactive debugging. When an agent needs to test WordPress features in a real browser, verify UI behavior, or interact with the admin dashboard, it can leverage MCP to programmatically navigate pages, inspect DOM elements, execute JavaScript, capture screenshots, and validate functionality without manual intervention.

The MCP server configuration is defined in [.mcp.json](../.mcp.json) and [.vscode/mcp.json](../.vscode/mcp.json), which specify the `chrome-devtools-mcp` server connecting to a Chrome instance running with remote debugging enabled on port 9222. This browser integration enables sophisticated testing workflows that combine WordPress CLI commands (wp-cli) with browser automation, such as resetting database state via CLI, then verifying the changes visually in the browser. The MCP approach provides a standardized protocol for agents to access external tools, making capabilities like browser control, API testing, or database inspection available across different AI clients (Claude Code, GitHub Copilot, Google Gemini).

### Usage in Development

With the MCP server configured, AI agents can execute browser-based workflows through natural language prompts. Instead of manually testing WordPress features, you can ask the agent to perform complex sequences like "login to wp-admin, navigate to the IONOS dashboard, enable MCP support in the tools tab, and verify the WordPress MCP plugin is activated." The agent will coordinate Chrome DevTools Protocol commands via MCP to automate the entire flow, capturing screenshots or console output as needed.

The MCP integration is particularly valuable for testing skills (see [Testing skill](#testing-skill) section below), which combine WordPress environment setup via wp-cli with browser-based verification. For example, a testing recipe can reset plugin state using wp-cli commands, then use MCP to visually confirm the reset by navigating to the dashboard and inspecting specific UI elements. This bridges the gap between backend WordPress operations and frontend user experience validation.

Example usage:

```
install and activate the plugins 'akisment' and 'woocommerce' in the wp-env development instance using `pnpm wp-env run cli wp ...`

use mcp server chrome-devtools to login to http://localhost:8888 using user `admin`and the password declared in WP_PASSWORD in the project .env file and go to the woocommerce plugin settings file.
```

### Browser Setup

To use the `chrome-devtools` MCP server, launch Chrome with remote debugging enabled:

```bash
# Linux/macOS
google-chrome --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-debug
```

Once Chrome is running with remote debugging, the MCP server can connect and provide browser control to AI agents. Future Chrome versions (145+) will simplify this setup with the `--auto-connect` flag, eliminating manual browser launch requirements.

## Skills

Beyond general coding standards, the agent configuration provides specialized [skills](https://agentskills.io/) for complex workflows. Skills are pre-configured automation recipes that agents can execute for tasks like running test suites, generating features with proper file structure, or performing multi-step operations. These skills leverage project-specific tools (wp-cli, pnpm scripts) and encode domain knowledge about the WordPress development workflow, making it possible to accomplish sophisticated tasks with simple natural language prompts.

Skills are particularly good for repetitive multi-step workflows that require specific tool invocations and domain knowledge, such as WordPress login sequences with wp-cli, plugin activation with MCP setup, or test suite execution with proper environment teardown. They capture proven procedures that would otherwise need to be rediscovered or explained each time, turning complex operations like "activate plugin X, configure feature Y, verify with Z" into single-command executions. This makes skills ideal for testing recipes, deployment procedures, environment setup tasks, and any workflow that involves coordinating multiple tools in a specific sequence.

List known skills :

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

The [testing skill](./skills/testing/SKILL.md) provides reusable test automation recipes for common WordPress workflows. It includes pre-configured procedures and guides for testing scenarios that combine test scenarios in human language and optional wp-cli commands (for resetting the instance). These recipes ensure consistent test execution across the team and eliminate the need to manually coordinate multiple tools for complex test scenariosand guides.

The testing skill (docs/skills/testing/SKILL.md) enables AI agents to:

- Discover recipes - List all available test scenarios

  `list available /testing receipes`

- Execute recipes and run tests declared in human language using Chrome DevTools MCP

  `execute /testing skill recipe login`

  `execute /testing skill recipe activate mcp`

- Setup environment - Run WP-CLI commands before testing
- Report results - Provide pass/fail status with details

- creae a new testing receipe :

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
