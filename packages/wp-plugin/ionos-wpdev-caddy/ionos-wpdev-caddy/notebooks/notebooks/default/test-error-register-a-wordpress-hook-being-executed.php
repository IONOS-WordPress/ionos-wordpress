<?php

/*
  Registers a WordPress hook being executed.
 */

\add_action('init', function () {
  echo 'huhu';
  wp_die();
});
