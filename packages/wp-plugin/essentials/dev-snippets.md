# essentials dev snippets

all interesting commands / instructions useful for development can be found here.

## load dashboard post from file

deleting the dashboard post in `/wp-admin/edit.php?post_type=custom_dashboard` will immediately recreate the dashboard with content loaded from file

## load dashboard global styles from global-styles.json
! make sure changes done locally in site-editor are saved to file !

`pnpm wp-env run cli wp post delete $(wp post list --post_type=wp_global_styles --format=ids) --force`

afterwards reload any wp-admin page

## reset nba actions

`pnpm wp-env run cli wp option delete ionos_nba_status`
