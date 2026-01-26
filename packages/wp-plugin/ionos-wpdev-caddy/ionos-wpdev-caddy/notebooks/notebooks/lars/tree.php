<?php

/**
 * Renders an ASCII-style tree structure for a given directory path,
 * optionally filtering files based on a whitelist of wildcard patterns.
 *
 * @param string $dir_path The starting directory path.
 * @param array $whitelist Array of glob patterns (e.g., ['*.php', 'assets/*.css']).
 * @param string $prefix Used internally for tree indentation.
 */
function render_ascii_tree(string $dir_path=ABSPATH, array $whitelist = [], string $prefix = ''): void
{
  // Sanitize the path and ensure it ends with a slash for glob()
  $dir_path = rtrim($dir_path, '/') . '/';

  // Get all contents (files and directories) sorted naturally
  $contents = @scandir($dir_path);

  if ($contents === false) {
    echo $prefix . "**[ERROR: Cannot read directory {$dir_path}]**\n";
    return;
  }

  // Filter out '.' (current) and '..' (parent)
  $contents = array_diff($contents, ['.', '..']);

  // Reset keys to use count() reliably
  $contents = array_values($contents);

  $count = count($contents);
  $index = 0;

  foreach ($contents as $item) {
    $index++;
    $path    = $dir_path . $item;
    $is_last = ($index === $count);

    // Determine the graphical lines for the current item
    $line = $is_last ? '└── ' : '├── ';

    // Determine the new prefix for sub-items
    $new_prefix = $prefix . ($is_last ? '    ' : '│   ');

    if (is_dir($path)) {
      // --- DIRECTORY BRANCH ---
      echo $prefix . $line . "**{$item}/**\n";
      // Recursively call the function for the subdirectory
      render_ascii_tree($path, $whitelist, $new_prefix);
    } else {
      // --- FILE LEAF ---

      // 1. If a whitelist is provided, check if the file matches
      $is_file_whitelisted = empty($whitelist);

      if (! $is_file_whitelisted) {
        // Check the file against every pattern in the whitelist
        foreach ($whitelist as $pattern) {
          // Prepend the full path to the pattern for glob matching
          $full_pattern = $dir_path . $pattern;

          // The 'glob' function is the most reliable way to check if a file
          // matches a standard shell wildcard pattern.
          if (glob($full_pattern)) {
            // The file exists and matches the pattern
            $is_file_whitelisted = true;
            break; // Stop checking patterns once a match is found
          }
        }
      }

      if ($is_file_whitelisted) {
        echo $prefix . $line . $item . "\n";
      }
      // If the file is not whitelisted, we skip displaying it.
    }
  }
}

render_ascii_tree(
  ABSPATH,
  [
    '*.php',       // All PHP files
    'index.html',  // The specific index.html file
    'assets/*.css', // All CSS files inside an 'assets' directory (at any level of recursion)
  ]
);
