<?php

namespace ionos\essentials\dashboard_mode;

function is_essentials_dashboard_mode()
{
  return \get_option('ionos_essentials_dashboard_mode', false);
}

add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {
    if (is_essentials_dashboard_mode()) {
        return admin_url('admin.php?page=ionos#overview');
    }

    return admin_url('index.php');
}, 10, 3);

add_action('init', function () {
  if (! is_essentials_dashboard_mode()) {
    return;
  }

  if (\is_user_logged_in()) {
    return;
  }
});
