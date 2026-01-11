# Testing Skill Recipes

This directory contains E2E test recipes for the WordPress monorepo. Each recipe defines a test scenario in human-readable format that the AI agent will execute using Chrome DevTools MCP.

## What is a Recipe?

A recipe is a markdown file that describes:
- **What to test** (the scenario)
- **How to set up** WordPress (optional WP-CLI commands)
- **Step-by-step instructions** for the E2E test
- **Expected outcomes** to verify
- **Cleanup steps** (optional)

## Recipe File Naming

- Use kebab-case: `my-test-scenario.md`
- Be descriptive: `plugin-activation.md` not `test1.md`
- Recipe name = filename without `.md` extension

## Recipe Template

```markdown
# Test Name

Brief description of what this recipe tests.

## Setup

Optional WP-CLI commands to prepare WordPress before testing:

\`\`\`bash
pnpm wp-env run cli wp plugin activate my-plugin
pnpm wp-env run cli wp option update some_setting "value"
pnpm wp-env run cli wp user create testuser test@example.com --role=subscriber
\`\`\`

## Test Steps

1. Navigate to [URL]
2. Wait for [element] to be visible
3. Click [button/link]
4. Fill in [field] with [value]
5. Submit/Click [action]
6. Wait for [result]

## Expected Outcomes

- Describe what should happen
- What should be visible on screen
- What state changes should occur
- What database changes should occur (can verify with WP-CLI)

## Verification Steps

Specific checks to verify the test passed:

1. Check URL contains [path]
2. Verify element with text [text] exists
3. Confirm [state] using WP-CLI:
   \`\`\`bash
   pnpm wp-env run cli wp [command to verify]
   \`\`\`

## Cleanup (Optional)

WP-CLI commands to reset WordPress to original state:

\`\`\`bash
pnpm wp-env run cli wp post delete [ID] --force
pnpm wp-env run cli wp user delete testuser --yes
\`\`\`
```

## Available Recipes

- **[login-flow.md](login-flow.md)** - Tests WordPress admin login
- **[create-post.md](create-post.md)** - Tests creating a new post in block editor

## Tips for Writing Good Recipes

1. **Be specific:** "Click the blue 'Publish' button in top right" not "click publish"
2. **Include waits:** "Wait for success message to appear" helps agent know when step is complete
3. **Use WP-CLI for verification:** More reliable than visual checks alone
4. **Add cleanup:** Keep test environment clean for next run
5. **Test prerequisites:** Mention if test requires login, plugins, or specific data
6. **Use CSS selectors:** When helpful, include selectors like `button.publish-button` or `#post-title`

## Running a Recipe

```bash
# With Claude Code
/testing recipe-name

# List all available recipes
/testing
```

## WordPress Environment Access

- **Frontend:** http://localhost:8888
- **Admin:** http://localhost:8888/wp-admin/
- **Username:** admin
- **Password:** From `.env` file `WP_PASSWORD` variable
- **WP-CLI:** `pnpm wp-env run cli wp [command]`

## Chrome DevTools MCP

The agent uses Chrome DevTools MCP to:
- Navigate to URLs
- Find elements by selector, text, or attributes
- Click, type, and interact with page elements
- Wait for elements, navigation, or network events
- Take screenshots
- Execute JavaScript if needed

## Example: Plugin Activation Recipe

```markdown
# Plugin Activation Test

Tests activating a plugin through WordPress admin.

## Setup

Ensure plugin is present but deactivated:

\`\`\`bash
pnpm wp-env run cli wp plugin deactivate my-plugin
\`\`\`

## Test Steps

1. Navigate to http://localhost:8888/wp-admin/plugins.php
2. Wait for plugins list to load
3. Find the row containing "My Plugin"
4. Click "Activate" link for that plugin
5. Wait for page reload

## Expected Outcomes

- Plugin should show as active
- Activation success message appears
- Plugin appears in "Active Plugins" section

## Verification Steps

1. Check for success message containing "Plugin activated"
2. Verify plugin row has "Deactivate" link (not "Activate")
3. Confirm with WP-CLI:
   \`\`\`bash
   pnpm wp-env run cli wp plugin list --name=my-plugin --field=status
   # Should output: active
   \`\`\`
```

## Contributing Recipes

When adding new recipes:
1. Create a new `.md` file in this directory
2. Follow the template structure
3. Test the recipe to ensure it works
4. Document any prerequisites or special setup needed
5. Include cleanup steps if recipe modifies WordPress state
