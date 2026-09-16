# Test-Coverage for Essentials

The following features (user-facing and internal) are implemented:

- **[#loop]** Test loop functionality

  test loop registration
  - implemented in
    - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/loop/tests/phpunit/LoopTest.php`

- **[#general]** Translation in different languages

  Look for some dedicated strings
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/dashboard-localization.spec.js

- **[#dashboard]** Dashboard is shown, no javascript error occurs
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/dashboard-no-errors.spec.js

- **[#dashboard]** click on tab changes tab
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/tabs.spec.js

- **[#dashboard]** Default dashboard

  On login, our dashboard appears. Change the toggle, then log out and log in. The standard dashboard appears.
  - implemented in
    - **❌ tests missing !**

- **[#dashboard]** Toggle Buttons

  The user clicks the toggle. The toggle state must persist after a page reload. No JavaScript error appears in the console.

- **[#dashboard]** Banner

  The banner appears and shows the correct tenant title.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/welcome.spec.js

- **[#dashboard]** My-account

  dashboard contains My Account block
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/dashboard-myaccount.spec.js

- **[#dashboard]** My-account

  Test whether two specific links exist for two different tenants.
  - implemented in
    - **❌ tests missing !**

- **[#dashboard]** NBA

  Dismiss a Next Best Action (NBA) item. It must stay dismissed. There must be at least three NBAs, for example.
  - implemented in
    - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/phpunit/ClassNBATest.php`

    - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/next-best-actions.spec.js`

- **[#dashboard]** Quick-links

  Test whether there are three links, each with an href and an anchor.
  - implemented in
    - **❌ tests missing !**

- **[#dashboard #wpscan]** WPScan / Vulnerability
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/wpscan.spec.js

- **[#dashboard #welcome]** Welcome screen

  The welcome screen appears and is clickable. The welcome message stays dismissed.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/welcome.spec.js

- **[#dashboard]** What's new

  The What's New section renders. The text is not tested.
  - implemented in

- **[#descriptify]** Adds text to the options-screen

  Check whether the text appears, or does not appear for other tenants.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/descriptify/tests/e2e/descriptify.spec.js

- **[#jetpack]** Injection of the jetpack coupon from the hosting environment

  Click through the process with a dummy coupon. Assert the URL parameter at the end.
  - implemented in
    - **❌ tests missing !**

- **[#login]** Add a logo to the login-screen

  Log out and check the logo on the login screen, by src link or screenshot.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/login/tests/e2e/login.spec.js

- **[#maintentance]** Hides content from users who are not logged in, and always shows content to the admin
  - Test idea: turn on maintenance mode, then check both logged-in and logged-out users.

  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/maintenance_mode/tests/e2e/maintenance.spec.js

- **[#migration]** Performs tasks while updating to a specific version

  Set the options to an old version, then assert the changes.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/migration/tests/phpunit/MigrationTest.php

- **[#security]** Credentials Checking (check password against haveibeenpwned.com)

  Set the password to "admin" and assert that it is detected.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/phpunit/ClassSecurityTest.php

- **[#security]** Make sure the user can enable or disable the security option, and that the setting persists
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/security-options.spec.js

- **[#security]** Prevent login with e-mail-address instead of username

  Try to log in with an e-mail address.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/e2e/security.spec.js

- **[#security]** Checks if SSL is enabled

  Assert that the SSL message appears on a random admin page.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/e2e/security.spec.js

- **[#security]** Disable access via xmlrpc

  Try to access the xmlrpc endpoint.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/e2e/security.spec.js

- **[#switchpage]** Provides a page where the user can decide whether to use AI for setup or not

  Assert that the switch page matches the screenshot.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/switch-page/tests/e2e/switch-page.spec.js

- **[#update]** Autoupdates the plugin

  Reset the version and assert that the update message appears.
  - implemented in
    - **❌ tests missing !**

- **[#wpscan]** Scans for vulnerabilities of plugins and themes

  Assert that the overview shows an issue, assert the admin notice, and assert that you can delete a plugin.
  - implemented in
    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/wpscan/tests/phpunit/ClassWPScanTest.php
