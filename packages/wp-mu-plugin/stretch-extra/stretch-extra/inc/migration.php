<?php

namespace ionos\stretch_extra\migration;

defined('ABSPATH') || exit();

const IONOS_MIGRATION_OPTION = 'IONOS_MIGRATION_OPTION';

\add_action('admin_init', function () {
  if (\get_option(IONOS_MIGRATION_OPTION) === "1.0.0") {
    return;
  }
// workaround only necessary until this is part of config API: https://hosting-jira.1and1.org/browse/GPHWPP-4254
  \wp_update_user([
    'ID'         => \get_current_user_id(),
    'user_email' => \get_option('admin_email')
  ]);
  update_option(IONOS_MIGRATION_OPTION, "1.0.0", true);
});
