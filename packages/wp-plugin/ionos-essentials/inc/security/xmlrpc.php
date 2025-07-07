<?php

namespace ionos\essentials\security;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

if ( ! \get_option( 'IONOS_SECURITY_FEATURE_OPTION[IONOS_SECURITY_FEATURE_OPTION_XMLRPC]' ) ) {
  return;
}


// Check if toggle is switched on.
if ( \get_option( 'IONOS_SECURITY_FEATURE_OPTION[IONOS_SECURITY_FEATURE_OPTION_XMLRPC]' ) ) {
  enable_xmlrpc_methods_allowlisting();
}

function ipv4_in_cidr( $ipv4, $cidr ) {
  list ( $subnet, $mask ) = explode( '/', $cidr );
  $subnet_addr            = ip2long( $subnet );
  $ip_addr                = ip2long( $ipv4 );
  $mask_addr              = -1 << ( 32 - $mask );
  return ( $subnet_addr & $mask_addr ) === ( $ip_addr & $mask_addr );
}

function ipv6_in_cidr( $ipv6, $cidr ) {
  list ( $subnet, $mask ) = explode( '/', $cidr );
  $subnet_addr            = inet_pton( $subnet );
  $ip_addr                = inet_pton( $ipv6 );
  $mask_addr              = inet_pton( str_repeat( 'f', $mask / 4 ) . ( $mask % 4 ? substr( 'f0', 2 - $mask % 4, 1 ) : '' ) );
  return ( $subnet_addr & $mask_addr ) === ( $ip_addr & $mask_addr );
}


function is_xmlrpc_request() {
  return defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
}


function disable_xmlrpc_methods() {
  add_filter( 'xmlrpc_methods', '__return_empty_array' );
}


function enable_xmlrpc_methods_allowlisting() {
  if ( ! is_xmlrpc_request() ) {
    return;
  }

  if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) {
    disable_xmlrpc_methods();
  }

  $ip = $_SERVER['REMOTE_ADDR'];

  if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false ) {
    $ipv4_allow_list = Config::get( 'features.xmlrpcGuard.allowLists.cidr.ipv4' );
    foreach ( $ipv4_allow_list as $cidr ) {
      if ( ipv4_in_cidr( $ip, $cidr ) !== false ) {
        return;
      }
    }
  }

  if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false ) {
    $ipv6_allow_list = Config::get( 'features.xmlrpcGuard.allowLists.cidr.ipv6' );
    foreach ( $ipv6_allow_list as $cidr ) {
      if ( ipv6_in_cidr( $ip, $cidr ) !== false ) {
        return;
      }
    }
  }

  disable_xmlrpc_methods();
}
