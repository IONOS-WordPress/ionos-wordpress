<?php

namespace ionos\essentials\security;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

\add_action('login_init', function () {
  \remove_filter('authenticate', 'wp_authenticate_email_password', 20);
  \add_filter(
    hook_name: 'authenticate',
    callback : function ($user, $username) {
      if (str_contains($username, '@')) {
        return new \WP_Error(
          'email_login_inactive',
          __('<strong>Error</strong>: The login with an email address is deactivated for this website. Please use your username instead.', 'ionos-security')
        );
      }

      return $user;
    },
    priority : 200,
    accepted_args: 2
  );
});
