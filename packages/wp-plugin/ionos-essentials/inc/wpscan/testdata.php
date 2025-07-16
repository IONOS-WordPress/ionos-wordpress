<?php

$testdata = [
  [
    'name'   => 'Critical Plugin Vulnerability',
    'slug'   => 'plugin-slug',
    'type'   => 'plugin',
    'update' => true,
    'score'  => 9.5,
  ],
  [
    'name'   => 'Critical Theme Vulnerability',
    'slug'   => 'theme-slug',
    'type'   => 'theme',
    'update' => true,
    'score'  => 8.0,
  ],
  [
    'name'   => 'Critical Theme Vulnerability no update',
    'slug'   => 'theme-slug',
    'type'   => 'theme',
    'update' => false,
    'score'  => 8.0,
  ],
  [
    'name'   => 'Plugin Warning',
    'slug'   => 'wordpress-version',
    'type'   => 'plugin',
    'update' => true,
    'score'  => 3.5,
  ],
  [
    'name'   => 'Plugin Warning no Update',
    'slug'   => 'not-installed-plugin',
    'type'   => 'plugin',
    'update' => false,
    'score'  => 6.0,
  ],
];

$testdata = array_slice($testdata, 0, 8);
