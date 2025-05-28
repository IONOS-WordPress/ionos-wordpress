# Dashboard feature

This is a wordpress plugin that provides a dashboard for our customers.
The dashboards can be created and edited using gutenberg.

### show dashboard

http://localhost:8888/wp-admin/admin.php?page=custom-page

### edit dashboard

http://localhost:8888/wp-admin/edit.php?post_type=ionos_dashboard

## concepts

### code split

The plugin code is split into two parts resembling the two features above.
To be able to edit and save the dashboard(s), the file `packages/wp-plugin/essentials/inc/dashboard/editor.php` is needed.

If the file is not available, the dashboard can still be viewed but not edited.
In the production release, the file is not included in the release and the dashboard can only be viewed.

For simply being able to view the dashboard (our MVP customer use case), the main plugin file is enough.

### reset nba actions

`pnpm wp-env run cli wp option delete ionos_nba_status`

### set wpscan token

`pnpm wp-env run cli wp option update ionos_security_wpscan_token random_invalid_token123 --allow-root`
