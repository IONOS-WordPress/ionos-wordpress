<?php

/*
  shows the status of the maintenance mode on sfs stretch
 */

(function () {
  $status = ionos\stretch_extra\maintenance\_get_maintenance_mode_status();

  if ($status['timestamp'] === null) {
    printf('Maintenance mode is inactive.');
    return;
  }

  $age = time() - $status['timestamp'];

  if ($status['expired']) {
    printf(
      'Maintenance mode is expired (activated %d seconds ago, limit is %d seconds).',
      $age,
      MAINTENANCE_EXPIRY_SECONDS,
    );
    return;
  }

  if ($status['active']) {
    printf('Maintenance mode is active (activated %d seconds ago).', $age);
    return;
  }

  printf('Maintenance mode is inactive.');
})();
