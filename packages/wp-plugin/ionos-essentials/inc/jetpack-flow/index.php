<?php

namespace ionos\essentials\jetpack_flow;

require_once 'inc/class-manager.php';

const INSTALL_JETPACK_OPTION_NAME   = 'assistant_jetpack_backup_flow_pending';

add_action('init', function () {
  Manager::init();
});
