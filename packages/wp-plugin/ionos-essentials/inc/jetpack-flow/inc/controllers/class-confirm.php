<?php

namespace ionos\essentials\jetpack_flow\Controllers;

class Confirm implements ViewController
{
  public static function setup()
  {
  }

  public static function render()
  {
    load_template(__DIR__ . '/../views/confirm.php', true);
  }

  public static function get_page_title()
  {
    return __('Confirm Jetpack installation', 'ionos-essentials');
  }
}
