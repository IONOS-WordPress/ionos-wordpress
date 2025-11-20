# Test-Coverage for Essentials

The following features (user faced and internal) are implemented:

- **[#loop]** Test loop functionality

    test loop registration

  - implemented in

    - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/loop/tests/phpunit/LoopTest.php`

- **[#general]** Translation in different languages

    Look for some dedicated strings

  - implemented in

    - **❌ tests missing !**

- **[#dashboard]** Dashboard is shown, no javascript error occurs

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/dashboard-no-errors.spec.js

- **[#dashboard]** click on tab changes tab

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/tabs.spec.js

- **[#dashboard]** Default dashboard

  on login, our dashboard comes, change toggle, logout and login, standard dashboard comes

  - implemented in

    - **❌ tests missing !**

- **[#dashboard]** Toggle Buttons

  user clicks on toogle, toggle should be there after page reload, no js-error in console

- **[#dashboard]** Banner

  the banner shows up, has the correct tenant title

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/welcome.spec.js

- **[#dashboard]** My-account

  dashboard contains My Account block

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/dashboard.spec.js

- **[#dashboard]** My-account

  test, if there are two specific links for two different tenants

  - implemented in

    - **❌ tests missing !**

- **[#dashboard]** NBA

  dismiss an item, should be dismissed, there should be at least three nba for example

  - implemented in

    - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/phpunit/ClassNBATest.php`

    - `packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/next-best-actions.spec.js`

- **[#dashboard]** Quick-links

  test, if there are three links with href and anchor

  - implemented in

    - **❌ tests missing !**

- **[#dashboard #wpscan]** WPScan / Vulnerability

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/wpscan.spec.js

- **[#dashboard #welcome]** Welcome screen

  Welcome screen is shown and clickable, welcomemessage remains dismissed

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/welcome.spec.js

- **[#dashboard]** What's new

  The whats new section is rendered, text is not tested

  - implemented in

- **[#descriptify]** Adds text to the options-screen

  check, if text is there or for other tenants not there

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/descriptify/tests/e2e/descriptify.spec.js

- **[#jetpack]** Injection of the jetpack coupon from the hosting environment

  click through process with dummy coupon, assert URL parameter at end

  - implemented in

    - **❌ tests missing !**

- **[#login]** Add a logo to the login-screen

  logout and check logo at login screen, by src-link or sceenshot

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/login/tests/e2e/login.spec.js

- **[#maintentance]** Hides contents for non logged-in users and shows contents to admin always
  - Test Idea: switch on maintenance mode, check for logged in and not logged in users

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/maintenance_mode/tests/e2e/maintenance.spec.js

- **[#migration]** Performs tasks while updating to a specific version

  set options to old version and assert changes

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/migration/tests/phpunit/MigrationTest.php

- **[#security]** Credentials Checking (check password against haveibeenpwned.com)

  set password to "admin" and assert to be detected

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/phppunit/ClassSecurityTest.php

- **[#security]** ensure user enables/disables security option and will be persisted

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/security-options.spec.js

- **[#security]** Prevent login with e-mail-address instead of username

  try to login with e-mail-adress

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/e2e/security.spec.js

- **[#security]** Checks if SSL is enabled

  assert ssl-message to be on a random adminpage

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/e2e/security.spec.js

- **[#security]** Disable access via xmlrpc

  try to access xmlrpc-endpoint

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/security/tests/e2e/security.spec.js

- **[#switchpage]** Provides a page wherepackages/wp-plugin/ionos-essentials/ionos-essentials/inc/dashboard/tests/e2e/dashboard-no-errors.spec.js the user can decide whether to use AI for setup or not

  assert switch page to be the same as screenshot

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/switch-page/tests/e2e/switch-page.spec.js

- **[#update]** Autoupdates the plugin

  reset version and assert to have update message

  - implemented in

    - **❌ tests missing !**

- **[#wpscan]** Scans for vulnerabilities of plugins and themes

  assert in overview to have an issue, assert adminnotice, assert to delete a plugin

  - implemented in

    - packages/wp-plugin/ionos-essentials/ionos-essentials/inc/wpscan/tests/phpunit/ClassWPScanTest.php

