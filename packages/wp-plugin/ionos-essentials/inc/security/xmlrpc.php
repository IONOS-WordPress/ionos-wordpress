<?php

namespace ionos\essentials\security;

const allowlistIP4 = [
              "122.248.245.244\/32",
              "54.217.201.243\/32",
              "54.232.116.4\/32",
              "192.0.80.0\/20",
              "192.0.96.0\/20",
              "192.0.112.0\/20",
              "195.234.108.0\/22" ];

const allowlistIP6 = [];

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

// Check if toggle is switched on.
enable_xmlrpc_methods_allowlisting();


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
    $ipv4_allow_list = allowlistIP4;
    foreach ( $ipv4_allow_list as $cidr ) {
      if ( ipv4_in_cidr( $ip, $cidr ) !== false ) {
        return;
      }
    }
  }

  if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false ) {

    $ipv6_allow_list = allowlistIP6;
    foreach ( $ipv6_allow_list as $cidr ) {
      if ( ipv6_in_cidr( $ip, $cidr ) !== false ) {
        return;
      }
    }
  }

  disable_xmlrpc_methods();
}
