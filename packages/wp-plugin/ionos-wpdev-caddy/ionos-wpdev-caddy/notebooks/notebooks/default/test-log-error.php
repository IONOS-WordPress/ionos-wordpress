<?php

/*
   writes something into the error log and check if it is logged
 */

printf(
  <<<EOF
  # DEBUG configuration

  WP_DEBUG%s
  WP_DEBUG_LOG%s
  WP_DEBUG_DISPLAY%s

  EOF
  ,
  defined('WP_DEBUG') ? '=' . json_encode(WP_DEBUG) : ' is not defined',
  defined('WP_DEBUG_LOG') ? '=' . json_encode(WP_DEBUG_LOG) : ' is not defined',
  defined('WP_DEBUG_DISPLAY') ? '=' . json_decode(WP_DEBUG_DISPLAY) : ' is not defined'
);

$error_log_path = ini_get('error_log');
if (empty($error_log_path)) {
  echo "The 'error_log' directive is NOT set to a custom file in php.ini.\n";
  echo "Errors are likely being sent to the SAPI error logger (e.g., the web server's main error log: /var/log/apache2/error.log or a similar file).";
} elseif ($error_log_path === 'syslog') {
  echo "The 'error_log' directive is set to 'syslog'.\n";
  echo "Errors are being sent to the system's logging facility.";
} else {
  echo 'The PHP error log path is: ' . $error_log_path . "\n\n";

  error_log('Mensch hier ist aber wirklich was in die Hose gegangen');

  echo "# last 20 lines of the error log file :\n\n";

  // Assume $error_log_path holds the path to your log file
  $num_lines  = 20;
  $log_lines  = [];
  $chunk_size = 4096; // Read the file in 4KB chunks

  try {
    // Check if the file exists and is readable
    if (! is_readable($error_log_path)) {
      throw new Exception("File not found or not readable: {$error_log_path}");
    }

    // Open the file for reading
    $file_handle = fopen($error_log_path, 'r');

    // Get the file size and set the current position to the end
    $file_size = filesize($error_log_path);
    $position  = $file_size;

    // Loop backward, reading chunks until we have enough lines
    while ($position > 0 && count($log_lines) < $num_lines) {
      // Calculate the starting position for the next chunk
      $start  = max(0, $position - $chunk_size);
      $length = $position - $start;

      // Move the pointer and read the chunk
      fseek($file_handle, $start);
      $chunk = fread($file_handle, $length);

      // Update the position for the next iteration
      $position = $start;

      // Split the chunk into lines
      $lines = explode("\n", $chunk);

      // Append the new lines to the beginning of the result array
      // array_splice is used to maintain the correct order and limit
      $log_lines = array_slice(array_merge($lines, $log_lines), -$num_lines, $num_lines);
    }

    fclose($file_handle);

    // Filter out potential empty lines from file start/end and output
    $final_lines = array_filter($log_lines);

    echo implode("\n", $final_lines);
  } catch (Exception $e) {
    echo 'Error reading log file: ' . $e->getMessage();
  }
}
