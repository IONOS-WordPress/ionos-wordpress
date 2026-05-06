# Dashboard MCP Activation Test

Tests enabling the MCP (Model Context Protocol) feature through the IONOS Dashboard, verifying plugin installation and application password generation.

## Setup

Clean up any existing MCP configuration and plugin:

```bash
pnpm wp-env run cli wp plugin delete wordpress-mcp --deactivate
pnpm wp-env run cli wp option delete wordpress_mcp_settings
pnpm wp-env run cli wp user application-password delete 1 --all
```

## Test Steps

### 1. Login to WordPress

1. Navigate to http://localhost:8888/wp-admin/
2. Wait for login form to be visible
3. Fill in username field with "admin"
4. Fill in password field with the password from WP_PASSWORD environment variable
5. Click the "Log In" button
6. Wait for navigation to WordPress dashboard

### 2. Navigate to IONOS Dashboard

1. Navigate to the IONOS Dashboard (likely at http://localhost:8888/wp-admin/admin.php?page=ionos-essentials or similar)
2. Wait for dashboard to load
3. Locate the MCP feature section/card

### 3. Enable MCP Feature

1. Find the MCP enable toggle/button/switch
2. Click to enable MCP
3. Wait for any confirmation dialogs or success messages
4. Wait for the MCP configuration process to complete
5. Verify success notification appears

### 4. Visual Verification

1. Check that MCP section shows as "enabled" or "active"
2. Verify any MCP configuration details are displayed (e.g., server URL, status)
3. Look for indication that wordpress-mcp plugin was installed

## Expected Outcomes

- MCP feature shows as enabled in IONOS Dashboard
- Success message or notification appears after activation
- Dashboard displays MCP connection details or configuration
- No error messages or warnings appear

## Verification Steps

### 1. Verify wordpress-mcp Plugin Installation

Check that the wordpress-mcp plugin was installed and activated:

```bash
pnpm wp-env run cli wp plugin list --name=wordpress-mcp --fields=name,status
```

Expected output:

- Plugin name: `wordpress-mcp`
- Status: `active`

Alternative verification:

```bash
pnpm wp-env run cli wp plugin is-active wordpress-mcp && echo "Plugin is active" || echo "Plugin is NOT active"
```

### 2. Verify MCP Settings Saved

Check that MCP settings were saved to options table:

```bash
pnpm wp-env run cli wp option get wordpress_mcp_settings
```

Should return MCP configuration data (not empty).

### 3. Verify Application Password Created

Check that an application password was generated for user ID 1 (admin):

```bash
pnpm wp-env run cli wp user application-password list 1 --format=count
```

Expected output: At least `1` (one or more application passwords exist)

Get details of the application password:

```bash
pnpm wp-env run cli wp user application-password list 1 --fields=name,created
```

Should show an application password (likely named related to MCP or wordpress-mcp).

## Additional Verification (Optional)

### Check Plugin Files Exist

```bash
pnpm wp-env run cli wp plugin path wordpress-mcp --dir
```

Should return a valid plugin directory path.

### Verify Plugin Metadata

```bash
pnpm wp-env run cli wp plugin get wordpress-mcp --fields=name,version,status
```

Should show plugin details with status `active`.

## Expected State After Test

- ✅ wordpress-mcp plugin installed and active
- ✅ wordpress_mcp_settings option exists with configuration
- ✅ At least one application password exists for admin user (ID 1)
- ✅ IONOS Dashboard shows MCP as enabled/active
- ✅ No errors in browser console or WordPress debug log

## Troubleshooting

If verification fails:

1. **Plugin not installed:**
   - Check IONOS Dashboard for error messages
   - Check browser console for JavaScript errors
   - Verify network requests completed successfully

2. **No application password:**
   - Check if user has capability to create application passwords
   - Verify WordPress version supports application passwords (5.6+)

3. **Settings not saved:**
   - Check browser console for AJAX errors
   - Verify nonce and permissions for settings save

## Cleanup (Optional)

To reset MCP configuration for next test run:

```bash
pnpm wp-env run cli wp plugin delete wordpress-mcp --deactivate
pnpm wp-env run cli wp option delete wordpress_mcp_settings
pnpm wp-env run cli wp user application-password delete 1 --all
```

## Notes

- This test requires the IONOS Essentials plugin to be installed and active
- The exact UI elements (buttons, toggles, etc.) may vary - adjust selectors as needed
- MCP activation may trigger background processes - allow time for completion
- Application password is generated automatically during MCP activation
- The wordpress-mcp plugin is installed from WordPress.org or a custom repository during activation
