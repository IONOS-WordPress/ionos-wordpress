# About

Skills extend AI client capabilities

# Note about `chrome-devtools-mcp`

The `chrome-devtools-mcp` MCP server gives the AI client access to the Chrome browser.

The initial connection setup is complex today. The upcoming Chrome 145 release adds an `--auto-connect` MCP setting that will simplify this setup.

# Cloude Code

## configuration

- `.claude/settings.json` : project specific settings

  ```
  {
    ...
    "enabledMcpjsonServers": ["chrome-devtools"],
    "enableAllProjectMcpServers": true,
    "skillDirs": ["./docs/skills"]
  }
  ```

- `.claude/CLAUDE.md` : claude specific `AGENTS.md` wrapper

- `.mcp.json` : project specific MCP servers, at least the `chrome-devtools` mcp server

# Gemini CLI

# configuration

`.gemini/settings.json` :

```
...
"experimental": {
  "skills": true
},
"mcpServers": {
  "chrome-devtools": {
    ...
  }
}
...
```

`.gemini/skills` are directly linked from `docs/skills`

# vscode copilot

- `.mcp.json` : project specific MCP servers, at least the `chrome-devtools` mcp server

vscode/copilot derives skills from the claude/gemini settings and the given project structure.

## Usage

see docs/10-ai-integration.md
