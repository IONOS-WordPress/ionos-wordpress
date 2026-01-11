# About

Skills extend AI client capabilities

# Cloude Code

## configuration

`.claude/settings.json` : project specific settings

`.claude/CLAUDE.md` : claude specific `AGENTS.md` wrapper

`.mcp.json` : project specific MCP servers

## Examples

---

```
create a new testing skill recipe dasbboard/mcp-activation.md which should

- login to wordpress
- go to ionos dashboard and enable mcp
- verify mcp is enabled by
  - testing plugin 'wordpress-mcp' is  installed and activated using wp-cli
  - testing an application password was generated

before the recipe the following wp-cli commands should be excuted

wp plugin delete wordpress-mcp
option delete wordpress_mcp_settingsuser application-password delete 1
```

---

```
list /testing recipes
```

---

```
execute /testing skill recipe login-flow
```

---

```
execute /testing skill recipe activate mcp
```
