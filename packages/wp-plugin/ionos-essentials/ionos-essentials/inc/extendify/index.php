<?php

namespace ionos\essentials\extendify;

defined('ABSPATH') || exit();

\add_action('admin_enqueue_scripts', function() {
  \wp_enqueue_style(
    'ionos-extendify',
    \plugin_dir_url(__FILE__) . 'index.css',
    [],
    filemtime(\plugin_dir_path(__FILE__) . 'index.css')
  );
});
