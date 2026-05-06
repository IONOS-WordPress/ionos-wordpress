<?php

/*
   Outputs information about PHP's configuration.
 */
ob_start();
phpinfo();
$info = ob_get_contents();
ob_end_clean();

// 1. Remove the entire <style>...</style> section
$info = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $info);

// 2. Strip all remaining HTML tags
$info = strip_tags($info);

// 3. Decode HTML entities (converts &#039; back to ')
$info = html_entity_decode($info);

// 4. Optional: Clean up excessive whitespace/newlines
$info = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $info);

echo $info;
