<?php

if (!array_key_exists('SFS', $_SERVER)) {
  // Not running on SFS WordPress hosting; do not load extra code.
  return;
}

@include_once '/opt/WordPress/extra/index.php';

