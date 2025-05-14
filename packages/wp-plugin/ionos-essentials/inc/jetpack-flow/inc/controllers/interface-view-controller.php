<?php

namespace ionos\essentials\jetpack_flow\Controllers;

interface ViewController {
  public static function get_page_title();
  public static function setup();
  public static function render();
}
