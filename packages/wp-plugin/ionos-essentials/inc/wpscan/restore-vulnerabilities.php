<?php
exec('wp transient delete ionos_wpscan_issues 2>&1', $output, $return_var);
exec('wp transient delete --all --network');

print_r($output);
print_r($return_var);

$wpcontentdir = dirname(getcwd(), 4);

create_fake_plugin('F4 Tree', 'f4-tree');
create_fake_plugin('o2tweet', 'o2tweet', '0.0.4');
create_fake_plugin('Gutenberg Blocks with AI by Kadence WP – Page Builder Features', 'kadence-blocks', '1.0.4');
create_fake_plugin('M Chart', 'm-chart', '1.0');
create_fake_plugin('Hack-info', 'hackinfo', '1.0');
create_fake_plugin('WP OAuth Server (OAuth Authentication)', 'oauth2-provider', '3.1.4');
create_fake_plugin('Namaste LMS', 'namaste-lms', '1.0.0');

create_fake_theme('Hara', 'hara');
create_fake_theme('Hasium', 'hasium');
create_fake_them('Hestia', 'hestia');
create_fake_theme('OceanWP', 'oceanwp');

function create_fake_theme($name, $slug, $version = '1.0.0')
{
  $theme_dir = $GLOBALS['wpcontentdir'] . '/themes/' . $slug;
  if (file_exists($theme_dir)) {
    array_map('unlink', glob("{$theme_dir}/*.*"));
    rmdir($theme_dir);
  }
  if (! file_exists($theme_dir)) {
    mkdir($theme_dir, 0755, true);
  }
  $style_file = $theme_dir . '/style.css';
  file_put_contents($style_file, "/*
Theme Name: {$name}
Description: Fake Theme for testing purposes.
Version: {$version}
Tags: example, test
Text Domain: {$slug}
*/\n");
  $index_file = $theme_dir . '/index.php';
  file_put_contents($index_file, "<?php\n\n// Silence is golden.\n");
  echo "Created fake theme: {$name}\n<br>";
}

function create_fake_plugin($name, $slug, $version = '1.0.0')
{
  $plugin_dir = $GLOBALS['wpcontentdir'] . '/plugins/' . $slug;
  if (file_exists($plugin_dir)) {
    array_map('unlink', glob("{$plugin_dir}/*.*"));
    rmdir($plugin_dir);
  }
  if (! file_exists($plugin_dir)) {
    mkdir($plugin_dir, 0755, true);
  }
  $plugin_file = $plugin_dir . '/' . $slug . '.php';
  file_put_contents($plugin_file, "<?php\n/*
Plugin Name: {$name}
Description: Fake Plugin for testing purposes.
Version: {$version}
*/
\n");
  echo "Created fake plugin: {$name}\n<br>";
}
