<?php

/*
  when data collector requests data via our rest endpoint we set an option IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS to current time.
  the cron job will run every week and tests if the last data collector request was made within last week.
  if this is not the case, we will register again our endpoint in the hope the data collector will find it again.
  this case may happen if the domain or endpoint url of our plugin was changed
 */

use function ionos\essentials\loop\_register_at_datacollector;
use const ionos\essentials\loop\IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS;

\add_action('init', function () {
  \wp_next_scheduled(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS)
  || \wp_schedule_event(time(), 'weekly', IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS);
});

\add_action(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS, function () {
  $loop_datacollector_last_access_time = \get_option(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS, 0);

  // if timestamp in $loop_datacollector_last_access_time is older than a week
  if ($loop_datacollector_last_access_time < strtotime('-1 week')) {
    _register_at_datacollector();
  }
});

\register_deactivation_hook(__FILE__, function () {
  $timestamp = \wp_next_scheduled(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS);
  if ($timestamp) {
    \wp_unschedule_event($timestamp, IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS);
  }
});
