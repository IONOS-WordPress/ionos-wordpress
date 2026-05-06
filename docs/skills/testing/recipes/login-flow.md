# Login Flow Test

Tests the WordPress admin login functionality with valid credentials.

## Setup

The user should not be logged in before the test.

## Test Steps

1. Navigate to http://localhost:8888/wp-admin/
2. Wait for login form to be visible
3. Fill in username field with "admin"
4. Fill in password field with the password from WP_PASSWORD environment variable
5. Click the "Log In" button
6. Wait for navigation to complete

## Expected Outcomes

- Should redirect to WordPress admin dashboard (URL contains `/wp-admin/`)
- Page should display "Dashboard" heading
- Admin toolbar should be visible at top of page
- Should see welcome panel or "Welcome to WordPress!" message
- No error messages should be displayed

## Verification Steps

1. Check current URL contains `/wp-admin/` or `/wp-admin/index.php`
2. Verify page title contains "Dashboard"
3. Look for element with text "Dashboard" in h1 or h2
4. Confirm presence of admin menu (wp-menu)

## Cleanup

The user should be logged out of the wordpress installation
