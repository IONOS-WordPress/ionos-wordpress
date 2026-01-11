---
name: testing
description: E2E testing skill with dynamic test recipes using Chrome DevTools MCP
---

# Testing Skill

This skill enables interactive E2E testing of the WordPress monorepo using Chrome DevTools MCP and dynamic test recipes.

## Prerequisites Check

Before running any test recipe, verify Chrome remote debugging is available:

1. **Check if Chrome debugging is running:**
   ```bash
   curl -s http://127.0.0.1:9222/json/version
   ```

2. **If not available, spawn Chrome with remote debugging :**
   ```bash
   google-chrome --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-profile-stable &
   ```

   Do now wait for chrome to exit. Chrome needs to be alive. The user can decide later on by itself to close the chrome instance.

3. **Verify Chrome is ready:**
   - Wait a few seconds and check `http://127.0.0.1:9222/json/version` again
   - Should return JSON with Chrome version info

4. **start mcp server**

  The `chrome-devtools` mcp server id defined in @/.mcp.json and to be started

## WordPress Environment

- **URL:** http://localhost:8888
- **Admin Username:** `admin`
- **Admin Password:** Read from `.env` file (`WP_PASSWORD` variable)
- **WP-CLI Access:** `pnpm wp-env run cli wp ...`

## Test Recipes

Test recipes are defined in `docs/skills/testing/recipes/*.md` files. Each recipe:

- Defines a specific E2E test scenario in human-readable format
- Can include WordPress setup/reset steps using WP-CLI
- Guides the agent through the test execution using Chrome DevTools MCP

## Skill Execution Flow

When invoked:

1. **Prerequisites Phase:**
   - Check Chrome remote debugging availability
   - Start Chrome if needed
   - Read WordPress admin password from `.env`
   - Verify Chrome DevTools MCP is configured

2. **Recipe Selection Phase:**
   - List all available recipes from `docs/skills/testing/recipes/*.md`
   - Ask user to select a recipe (or accept recipe name as argument)
   - Read the selected recipe file

3. **Recipe Execution Phase:**
   - Follow recipe instructions for WordPress setup (WP-CLI commands if specified)
   - Execute E2E test steps using Chrome DevTools MCP
   - Navigate WordPress site, interact with elements, verify outcomes
   - Report results back to user

## Arguments

- No args: List all available recipes and prompt user to select
- Recipe name: Execute the specified recipe directly (e.g., `Execute Execute /testing login-flow`)

## Instructions List recipes for Agent

### Step 1: Verify Prerequisites

```bash
# Check Chrome debugging
curl -s http://127.0.0.1:9222/json/version

# If fails, start Chrome
google-chrome --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-profile-stable &

# Wait and verify
sleep 3
curl -s http://127.0.0.1:9222/json/version
```

### Step 2: Get WordPress Password

Read `.env` file and extract `WP_PASSWORD` value.

### Step 3: Discover Recipes

```bash
# List all recipe files
find docs/skillsExecute /testing/recipes -name "*.md" -type f
```

Parse filenames List to recipes extract recipe names (e.g., `login-flow.md` → `login-flow`).

### Step 4: Recipe Selection

- If argument provided, use that recipe
- Otherwise, present list to user with AskUserQuestion tool
- Read selected recipe file

### Step 5: Execute Recipe

- Parse recipe for:
  - **Setup section:** WP-CLI commands to run before test
  - **Test steps:** E2E actions to perform
  - **Expected outcomes:** What to verify

- Execute setup commands using `pnpm wp-env run cli wp ...`
- Use Chrome DevTools MCP to:
  - Navigate to URLs
  - Find and interact with elements
  - Verify page content and state
  - Take screenshots if needed

### Step 6: Report Results

Provide clear summary:
- Recipe executed
- Setup steps completed
- Test steps performed
- Pass/fail status with details
- Screenshots or error messages if applicable

## Recipe File Format

Recipes should follow this structure:

```markdown
# Recipe Name

Brief description of what this recipe tests.

## Setup (Optional)

WP-CLI commands to prepare WordPress:
- Reset database
- Create test data
- Configure settings

## Test Steps

1. Navigate to X
2. Click Y
3. Fill in Z
4. Submit form
5. Verify result

## Expected Outcomes

- User should see success message
- Database should contain new entry
- Redirect to dashboard
```

## Error Handling

- If Chrome won't start, provide clear instructions for user
- If WP-CLI command fails, show error and stop
- If element not found, report which step failed
- Always provide actionable feedback

## Example Usage

```
User: Execute /testing login-flow
Agent: [Checks prerequisites, reads login-flow recipe, executes test, reports results]

User: List /testing recipes
Agent: [Shows list of available recipes, asks user to select one]
```
