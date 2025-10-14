# Dashboard feature

This feature provides a dashboard for our customers.

## snippets

- show dashboard : http://localhost:8888/wp-admin/

- reset all nba related options at once:

  ```
  pnpm wp-env run cli wp option delete ionos_nba_status ionos_essentials_nba_setup_completed ionos_essentials_loop_nba_actions_shown
  ```

- reset nba actions : `pnpm wp-env run cli wp option delete ionos_nba_status`
- reset nba setup status: `pnpm wp-env run cli wp option delete ionos_essentials_nba_setup_completed`
- reset nba actions shown: `pnpm wp-env run cli wp option delete ionos_essentials_loop_nba_actions_shown`

- set wpscan token : `pnpm wp-env run cli wp option update ionos_security_wpscan_token random_invalid_token123 --allow-root`

- reset welcome screen: `pnpm wp-env run cli wp user meta delete 1 ionos_essentials_welcome`
