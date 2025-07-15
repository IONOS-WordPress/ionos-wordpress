# Dashboard feature

This feature provides a dashboard for our customers.

## snippets

- show dashboard : http://localhost:8888/wp-admin/

- reset nba actions : `pnpm wp-env run cli wp option delete ionos_nba_status`

- set wpscan token : `pnpm wp-env run cli wp option update ionos_security_wpscan_token random_invalid_token123 --allow-root`

- reset welcome screen: `pnpm wp-env run tests-cli wp user meta delete 1 ionos_essentials_welcome`
