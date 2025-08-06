<?php

namespace ionos\essentials\security;

const LEAKED_CREDENTIALS_FLAG_NAME = 'ionos_compromised_credentials_check_leak_detected_v2';
const IONOS_NOREPLY_EMAIL          = 'no-reply@wpservice.io';

defined('ABSPATH') || exit();

\add_action(
  hook_name: 'check_passwords',
  callback: function ($user_login, $pass1, $pass2) {
    if (empty($pass1) || $pass1 !== $pass2) {
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
      $mail = filter_input(INPUT_GET, 'mail', FILTER_SANITIZE_EMAIL);
      include __DIR__ . '/views/password-reset-necessary.php';
      exit;
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
          ], \wp_login_url());

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

\add_action('admin_notices', function (): void {
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

function obfuscate_email($email): string
{
  list($user, $domain) = explode('@', $email);
  $domain_parts        = explode('.', $domain);
  $tld                 = array_pop($domain_parts);
  $domain_name         = implode('.', $domain_parts);

  foreach ([&$user, &$domain_name] as &$string) {
    switch (strlen($string)) {
      case 1:
        $string = '*';
        break;
      case 2:
      case 3:
        $string = substr($string, 0, 1) . str_repeat('*', strlen($string) - 1);
        break;
      default:
        $string = substr($string, 0, 1) . str_repeat('*', max(0, strlen($string) - 2)) . substr($string, -1);
        break;
    }

    unset($string);
  }

  return $user . '@' . $domain_name . '.' . $tld;
}

function is_leaked($password): ?bool
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

function has_leaked_flag($user_id): bool
{
  return (bool) \get_user_meta($user_id, LEAKED_CREDENTIALS_FLAG_NAME, true);
}

\add_action('login_enqueue_scripts', function (): void {
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

function is_valid_email($email): bool
{
  return IONOS_NOREPLY_EMAIL !== $email && is_email($email);
}
