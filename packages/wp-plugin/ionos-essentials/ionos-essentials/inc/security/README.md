# development

- set admin user password to something different : `pnpm wp-env run tests-cli wp user update admin --user_pass=g0lasch0815!`

  If you set the password to a leaked password you can no more login because to leaked password info will be persisted as user meta. In this case you need to
  - reset the password to a not leaked password : `pnpm wp-env run tests-cli wp user update admin --user_pass=c0ronasch0815!`

  - reset the user meta : `pnpm wp-env run tests-cli wp user meta delete admin ionos_compromised_credentials_check_leak_detected_v2`
