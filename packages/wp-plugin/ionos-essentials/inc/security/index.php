<?php

namespace ionos\essentials\security;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

const IONOS_SECURITY_FEATURE_OPTION = 'IONOS_SECURITY_FEATURE_OPTION';
const IONOS_SECURITY_FEATURE_OPTION_XMLRPC = 'IONOS_SECURITY_FEATURE_OPTION_XMLRPC';
const IONOS_SECURITY_FEATURE_OPTION_PEL = 'IONOS_SECURITY_FEATURE_OPTION_PEL';
const IONOS_SECURITY_FEATURE_OPTION_SSL = 'IONOS_SECURITY_FEATURE_OPTION_SSL';
const IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING = 'IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING';
const IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY = 'IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY';

const IONOS_SECURITY_FEATURE_OPTION_DEFAULT = [
  IONOS_SECURITY_FEATURE_OPTION_XMLRPC => true,
  IONOS_SECURITY_FEATURE_OPTION_PEL => true,
  IONOS_SECURITY_FEATURE_OPTION_SSL => true,
  IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING => true,
  IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY => true,
];

\add_action(   'init',   function () {
  $security_options = \get_option(IONOS_SECURITY_FEATURE_OPTION, IONOS_SECURITY_FEATURE_OPTION_DEFAULT);

  if ($security_options[IONOS_SECURITY_FEATURE_OPTION_XMLRPC] === true) {
    require_once __DIR__ . '/xmlrpc.php';
  }
  if ($security_options[IONOS_SECURITY_FEATURE_OPTION_PEL] === true) {
    require_once __DIR__ . '/pel.php';
  }
  if ($security_options[IONOS_SECURITY_FEATURE_OPTION_SSL] === true) {
    require_once __DIR__ . '/ssl.php';
  }
  if ($security_options[IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING] === true) {
    require_once __DIR__ . '/credentials-checking.php';
  }
  if ($security_options[IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY] === true) {
    require_once __DIR__ . '/vulnerability-scan.php';
  }
});
