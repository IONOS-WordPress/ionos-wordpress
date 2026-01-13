# About

Skills extend AI client capabilities

# Note about `chrome-devtools-mcp`

The `chrome-devtools-mcp` MCP server provides the AI client access to the chrome browser.

The intial connection setup will get a lot easier with the release of the upcoming Chrome 145 (mcp setting `--autoconnect` will do the magic in the future).

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

Skills will be derived by vscode/copilot from the claude/gemini settings and given project structure.

## Usage

see docs/10-ai-integration.md
