<?php

namespace ionos\essentials;

/*
  utility class to get precomputed tenant information
 */

defined('ABSPATH') || exit();

class Tenant
{
  public readonly string $label;

  private static ?Tenant $instance = null;

  private function __construct(
    public readonly string $name
  ) {
    $this->label = match ($name) {
      'ionos'     => 'IONOS',
      'fasthosts' => 'Fasthosts',
      'homepl'    => 'home.pl',
      'arsys'     => 'Arsys',
      'piensa'    => 'Piensa Solutions',
      'strato'    => 'STRATO',
      'udag'      => 'UDAG',
    };
  }

  public static function get_instance(): self
  {
    if (! self::$instance instanceof self) {
      self::$instance = new self(strtolower(\get_option('ionos_group_brand', 'ionos')));
    }

    return self::$instance;
  }
}
