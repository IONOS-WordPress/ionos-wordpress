<?php

namespace ionos\essentials\security;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

const IONOS_SECURITY_ALLOW_LIST_IP4 = [
  "122.248.245.244\/32",
  "54.217.201.243\/32",
  "54.232.116.4\/32",
  "192.0.80.0\/20",
  "192.0.96.0\/20",
  "192.0.112.0\/20",
  "195.234.108.0\/22", ];

const IONOS_SECURITY_ALLOW_LIST_IP6 = [];

function _ipv4_in_cidr($ipv4, $cidr)
{
  list($subnet, $mask)    = explode('/', $cidr);
  $subnet_addr            = ip2long($subnet);
  $ip_addr                = ip2long($ipv4);
  $mask_addr              = -1 << (32 - $mask);
  return ($subnet_addr & $mask_addr) === ($ip_addr & $mask_addr);
}

function _ipv6_in_cidr($ipv6, $cidr)
{
  list($subnet, $mask)    = explode('/', $cidr);
  $subnet_addr            = inet_pton($subnet);
  $ip_addr                = inet_pton($ipv6);
  $mask_addr              = inet_pton(
    str_repeat('f', $mask / 4) . ($mask % 4 ? substr('f0', 2 - $mask % 4, 1) : '')
  );
  return ($subnet_addr & $mask_addr) === ($ip_addr & $mask_addr);
}

function _disable_xmlrpc_methods()
{
  \add_filter('xmlrpc_methods', '__return_empty_array');
}

(function () {
  if (! defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
    return;
  }

  if (! isset($_SERVER['REMOTE_ADDR'])) {
    _disable_xmlrpc_methods();
  }

  $ip = $_SERVER['REMOTE_ADDR'];

  if (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $ipv4_allow_list = IONOS_SECURITY_ALLOW_LIST_IP4;
    foreach ($ipv4_allow_list as $cidr) {
      if (false !== _ipv4_in_cidr($ip, $cidr)) {
        return;
      }
    }
  }

  if (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

    $ipv6_allow_list = IONOS_SECURITY_ALLOW_LIST_IP6;
    foreach ($ipv6_allow_list as $cidr) {
      if (false !== __ipv6_in_cidr($ip, $cidr)) {
        return;
      }
    }
  }

  __disable_xmlrpc_methods();
})();
