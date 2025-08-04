# Test-Coverage for Essentials

The following features (user faced and internal) are implemented:

| Area             | Feature/Description                                                          | Test Idea                                                                                | e2e/phpunit/monitor | Notes |
| ---------------- | ---------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- | ----------- | ----- |
| General          | Translation in different languages                                           | Look for some dedicated strings                                                          |             |       |
| Dashboard        | General                                                                      | Dasboad is shown, no javascript error occurs                                             |             |       |
|                  | Tabs                                                                         | click on tab changes tab                                                                 |             |       |
|                  | Default dashboard                                                            | on login, our dashboard comes, change toggle, logout and login, standard dashboard comes |             |       |
|                  | Toggle Buttons                                                               | user clicks on toogle, toggle should be there after page reload                          |             |       |
|                  | Banner                                                                       | the banner shows up, has the correct tenant logo and buttons                             |             |       |
|                  | My-account                                                                   | test, if there are two specific links for two different tenants                          |
|                  | NBA                                                                          | dismiss an item, should be dismissed, there should be at least three nba for example     |             |       |
|                  | Quick-links                                                                  | test, if there are three links with href and anchor                                      |             |       |
|                  | Vulnerability                                                                | _will be tested in wpscan section_                                                       |             |       |
|                  | Welcome screen                                                               | Welcome screen is shown as on screenshot                                                 |             |       |
|                  | What's new                                                                   | The whats new section is rendered, text is not tested                                    |             |       |
| Descriptify      | Adds text to the options-screen                                              | check, if text is there or for other tenants not there                                   |             |       |
| Jetpack-Flow     | Injection of the jetpack coupon from the hosting environment                 |                                                                                          |             |       |
| Login            | Add a logo to the login-screen                                               | logout and check logo at login screen                                                    |             |       |
| Maintenance mode | Hides contents for non logged-in users and shows contents to admin always    | switch on maintenance mode, check for logged in and not logged in users                  |             |       |
| Migration        | Performs tasks while updating to a specific version                          |                                                                                          |             |       |
| Security         | Credentials Checking (check password against haveibeenpwned.com)             | set password to "admin" and assert to be detected                                        |             |       |
|                  | Prevent login with e-mail-address instead of username                        | try to login with e-mail-adresse                                                         |             |       |
|                  | Checks if SSL is enabled                                                     | assert ssl-message to be on a random adminpage                                           |             |       |
|                  | Disable access via xmlrpc                                                    | try to access xmlrpc-endpoint                                                            |             |       |
| Switch Page      | Provides a page where the user can decide whether to use AI for setup or not | assert switch page to be the same as screensht                                           |             |       |
| Update           | Autoupdates the plugin                                                       | reset version and assert to have update message                                          |             |       |
| WPScan           | Scans for vulnerabilities of plugins and themes                              | assert in overview to have an issue, assert adminnotice, assert to delete a plugin       |             |       |
