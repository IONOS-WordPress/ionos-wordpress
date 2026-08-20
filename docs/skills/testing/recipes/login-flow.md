# Login Flow Test

Tests the WordPress admin login functionality with valid credentials.

## Setup

The user should not be logged in before the test.

## Test Steps

1. Navigate to http://localhost:8888/wp-admin/
2. Wait for the login form to be visible
3. Fill in the username field with "admin"
4. Fill in the password field with the password from the WP_PASSWORD environment variable
5. Click the "Log In" button
6. Wait for the navigation to finish

## Expected Outcomes

- The browser should redirect to the WordPress admin dashboard (the URL contains `/wp-admin/`)
- The page should display a "Dashboard" heading
- The admin toolbar should be visible at the top of the page
- The user should see a welcome panel or a "Welcome to WordPress!" message
- No error messages should appear

## Verification Steps

1. Check that the current URL contains `/wp-admin/` or `/wp-admin/index.php`
2. Verify that the page title contains "Dashboard"
3. Look for an element with the text "Dashboard" in an h1 or h2 tag
4. Confirm the presence of the admin menu (wp-menu)

## Cleanup

The user should be logged out of the WordPress installation
