<?php

/*
   test writing to .htaccess
 */

$files_and_dirs = scandir(ABSPATH);

if ($files_and_dirs === false) {
  echo 'Error: Could not read the directory at ' . ABSPATH;
} else {
  echo '# Files and Directories in ABSPATH(=' . ABSPATH . "):\n\n";

  foreach ($files_and_dirs as $item) {
    // Exclude the current directory (.) and parent directory (..)
    if ($item === '.' || $item === '..') {
      continue;
    }

    // Check if the item is a directory or a file for better presentation
    $item_path = ABSPATH . $item;
    $item_type = is_dir($item_path) ? 'Directory' : 'File';

    echo str_pad($item, 30) . ' [' . $item_type . "]\n";
  }
}

$ht_access = ABSPATH . '.htaccess';
if (! file_exists($ht_access)) {
  printf("\n.htaccess file does not exist.\n");

  if (touch($ht_access)) {
    printf(
      "\n✅ The empty .htaccess file has been created or its timestamp has been updated at: %s\n",
      htmlspecialchars($ht_access)
    );
  } else {
    printf(
      "\n❌ Error Failed to create the .htaccess file. This usually means PHP does not have the **necessary write permissions** for the directory: %s\n",
      ABSPATH
    );
  }

  return;
}

// 1. Get the raw file permissions
$perms = fileperms($ht_access);

// 2. Determine file type (first character)
$info = 'Unknown Type';
if (($perms & 0xC000) == 0xC000) {
  $info = 's';
} // Socket
elseif (($perms & 0xA000) == 0xA000) {
  $info = 'l';
} // Symbolic Link
elseif (($perms & 0x8000) == 0x8000) {
  $info = '-';
} // Regular File
elseif (($perms & 0x6000) == 0x6000) {
  $info = 'b';
} // Block special
elseif (($perms & 0x4000) == 0x4000) {
  $info = 'd';
} // Directory
elseif (($perms & 0x2000) == 0x2000) {
  $info = 'c';
} // Character special
elseif (($perms & 0x1000) == 0x1000) {
  $info = 'p';
} // FIFO pipe

// 3. Determine permissions (the next nine characters)
// Use bitwise operators to check for read (r), write (w), and execute (x) for owner, group, and others.
$info .= (($perms & 0x0100) ? 'r' : '-'); // Owner Read
$info .= (($perms & 0x0080) ? 'w' : '-'); // Owner Write
$info .= (($perms & 0x0040) ?
          (($perms & 0x0800) ? 's' : 'x') :
          (($perms & 0x0800) ? 'S' : '-')); // Owner Execute/SetUID

$info .= (($perms & 0x0020) ? 'r' : '-'); // Group Read
$info .= (($perms & 0x0010) ? 'w' : '-'); // Group Write
$info .= (($perms & 0x0008) ?
          (($perms & 0x0400) ? 's' : 'x') :
          (($perms & 0x0400) ? 'S' : '-')); // Group Execute/SetGID

$info .= (($perms & 0x0004) ? 'r' : '-'); // Others Read
$info .= (($perms & 0x0002) ? 'w' : '-'); // Others Write
$info .= (($perms & 0x0001) ?
          (($perms & 0x0200) ? 't' : 'x') :
          (($perms & 0x0200) ? 'T' : '-')); // Others Execute/Sticky

echo "\n\n# .htaccess Permissions (Symbolic):\n\n";
echo 'The symbolic permissions are: ' . $info . "\n";

printf("\n# Contents of .htaccess file\n\n%s", file_get_contents($ht_access));
