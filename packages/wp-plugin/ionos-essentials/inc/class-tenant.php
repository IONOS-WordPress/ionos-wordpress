<?php

namespace ionos\essentials;

/*

  utilkity class to get precomputed tenant information

*/

defined('ABSPATH') || exit();

class Tenant {
  private static ?Tenant $instance = null;

  readonly string $label;

  private function __construct(readonly string $name) {
    $this->label = match($name) {
      'ionos' => 'IONOS',
      'fasthosts' => 'Fasthosts',
      'homepl' => 'home.pl',
      'arsys' => 'Arsys',
      'piensa' => 'Piensa Solutions',
      'strato'  => 'STRATO',
      'udag' => 'UDAG',
    };
  }

  public static function get_instance(): Tenant {
    if (null === self::$instance) {
      self::$instance = new self(strtolower(\get_option('ionos_group_brand', 'ionos')));
    }
    return self::$instance;
  }
}

