<?php

namespace ionos\essentials\security;

use const ionos\essentials\PLUGIN_FILE;

const LEAKED_CREDENTIALS_FLAG_NAME = 'ionos_compromised_credentials_check_leak_detected_v2';
const IONOS_NOREPLY_EMAIL          = 'no-reply@wpservice.io';

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

\add_action(
  hook_name: 'check_passwords',
  callback: function ($user_login, $pass1, $pass2) {
    if ($pass1 !== $pass2) {
      return;
    }

    if (! is_leaked($pass1)) {
      $user = \wp_get_current_user();
      \update_user_meta($user->ID, LEAKED_CREDENTIALS_FLAG_NAME, false);
      return;
    }

    \add_action(
      'user_profile_update_errors',
      fn (\WP_Error $errors) => $errors->add(
        'password_leaked',
        __('The entered password has already been leaked. Please choose another one.', 'ionos-security')
      )
    );
  },
  accepted_args : 3
);

if (is_login()) {
  \add_action('login_form_icc_leak_detected', function () {
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ('icc_leak_detected' === $action) {
      \add_action('login_header', function () {
        $mail = filter_input(INPUT_GET, 'mail', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        printf(
          '<div class="wrapper">
            <div class="header">
                <img class="logo" src="%s" />
            </div>
            <div class="container">
                <div class="content">
                    <h1 class="headline">%s</h1>
                    <p>%s</p>
                    <p><em>%s</em> %s</p>
                </div>
            </div>
        </div>',
          \plugins_url(
            'inc/dashboard/data/tenant-logos/' . \get_option('ionos_group_brand', 'ionos') . '.svg',
            PLUGIN_FILE
          ),
          $mail . \esc_html__('Security Notice', 'ionos-security'),
          \esc_html__(
            "It looks like your password has been compromised. To protect the security of your account, it's crucial that you change your password immediately. This will ensure that your personal and sensitive information remains safe and secure. An email was sent to your email address. Please follow the instruction to reset your password.",
            'ionos-security'
          ),
          \esc_html__('Additional Information:', 'ionos-security'),
          sprintf(
            \esc_html__(
              'To check if your password has been compromised we are using the free service %1$s. For that we encrypt your password and send parts of the encryption to the service.',
              'ionos-security'
            ),
            '<a href="https://haveibeenpwned.com/Passwords" target="_blank" rel="nofollow noopener noreferrer">Have I Been Pwned</a>'
          )
        );
      });
    }
  });

  \add_action(
    hook_name: 'validate_password_reset',
    callback : function ($errors, $user) {
      $pass1 = filter_input(INPUT_POST, 'pass1');
      $pass2 = filter_input(INPUT_POST, 'pass2');

      if (empty($pass1) || $pass1 !== $pass2) {
        return;
      }

      if (true === is_leaked($pass1)) {
        $errors->add(
          'password_leaked',
          __('The entered password has already been leaked. Please choose another one.', 'ionos-security')
        );
      } else {
        \update_user_meta($user->ID, LEAKED_CREDENTIALS_FLAG_NAME, false);
      }
    },
    accepted_args : 2
  );

  \add_filter(
    hook_name: 'authenticate',
    callback : function ($user, $username, $password) {
      if (\is_wp_error($user) || null === $user || empty($password)) {
        return $user;
      }

      $is_password_leaked = has_leaked_flag($user->ID);

      // Don't check for a leak, on a flagged account.
      if (false === $is_password_leaked) {
        $is_password_leaked = is_leaked($password);
        \update_user_meta($user->ID, LEAKED_CREDENTIALS_FLAG_NAME, $is_password_leaked);
      }

      if (false === is_valid_email($user->user_email) || false === $is_password_leaked) { // phpcs:ignore
        return $user;
      }

      \add_filter(
        hook_name : 'ionos_login_redirect_to',
        callback : function () use ($user) {
          \wp_logout();

          $user_login = filter_input(INPUT_POST, 'log');
          if (empty($user_login)) {
            return;
          }

          \retrieve_password($user_login);

          $url = \add_query_arg([
            'action' => 'icc_leak_detected',
            'mail'   => obfuscate_email($user->user_email),
          ], wp_login_url());

          \wp_safe_redirect($url);
          exit;
        },
        priority : 200
      );
      return new \WP_Error(
        'ionos_password_leaked',
        __('It looks like your password has been compromised. To protect the security of your account, it‘s crucial that you change your password immediately. This will ensure that your personal and sensitive information remains safe and secure. An email was sent to your email address. Please follow the instruction to reset your password.', 'ionos-security')
      );
    },
    priority : 100,
    accepted_args : 3
  );
}

\add_action('admin_notices', function () {
  if (has_leaked_flag(get_current_user_id())) {
    $class   = 'notice notice-error';
    $message = __('We detected that your password has been leaked and suggest that you change it as soon as possible.', 'ionos-security');
    $link    = sprintf(
      '<a href="%s">%s</a>',
      \esc_url(\get_edit_profile_url()),
      \esc_html__('Click here to edit your profile settings.', 'ionos-security')
    );
    printf('<div class="%s"><p>%s %s</p></div>', \esc_attr($class), \esc_html($message), \esc_url($link));
  }
});

function obfuscate_email($email)
{
  list($user, $domain) = explode('@', $email);
  $domain_parts        = explode('.', $domain);
  $tld                 = array_pop($domain_parts);
  $domain_name         = implode('.', $domain_parts);

  $user =  (strlen($user) > 3) ? substr($user, 0, 2) . str_repeat('*', max(0, strlen($user) - 2)) : '***';
  $domain_name = (strlen($domain_name) > 3) ? str_repeat('*', max(0, strlen($domain_name) - 2)) . substr(
    $domain_name,
    -1
  ) : '***';

  return $user . '@' . $domain_name . '.' . $tld;
}

function is_leaked($password)
{
  $hash   = strtoupper(sha1($password));
  $prefix = substr($hash, 0, 5);
  $suffix = substr($hash, 5);

  $url      = "https://api.pwnedpasswords.com/range/{$prefix}";
  $response = \wp_remote_get($url);
  if (\is_wp_error($response)) {
    return null;
  }

  return str_contains(\wp_remote_retrieve_body($response), $suffix);
}

function has_leaked_flag($user_id)
{
  return true; // todo: remove this line when the feature is stable
  return (bool) \get_user_meta($user_id, LEAKED_CREDENTIALS_FLAG_NAME, true);
}

\add_action('login_enqueue_scripts', function () {
  $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  if ('icc_leak_detected' === $action) {
    \wp_enqueue_style(
      'ionos-credentials-checking',
      \plugins_url('style.css', __FILE__),
      [],
      filemtime(__DIR__ . '/style.css')
    );
  }
});

function is_valid_email($email)
{
  return IONOS_NOREPLY_EMAIL !== $email && is_email($email);
}
