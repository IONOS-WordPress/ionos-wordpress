<?php

namespace ionos\essentials;

/*
  utility class to get precomputed tenant information
 */

defined('ABSPATH') || exit();

class Tenant
{
  private string $_label = 'IONOS';

  private static ?Tenant $instance = null;

  private function __construct(
    private string $_slug
  ) {
    $this->_label = match ($_slug) {
      'ionos'     => 'IONOS',
      'fasthosts' => 'Fasthosts',
      'homepl'    => 'home.pl',
      'arsys'     => 'Arsys',
      'piensa'    => 'Piensa Solutions',
      'strato'    => 'STRATO',
      'udag'      => 'United Domains',
      default     => 'IONOS',
    };
  }

  public static function get_slug(): string
  {
    return self::_get_instance()->_slug;
  }

  public static function get_label(): string
  {
    return self::_get_instance()->_label;
  }

  private static function _get_instance(): self
  {
    if (! self::$instance instanceof self) {
      self::$instance = new self(strtolower(\get_option('ionos_group_brand', 'ionos')));
    }

    return self::$instance;
  }
}
