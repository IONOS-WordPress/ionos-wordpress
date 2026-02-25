<?php

/*
* Activate maintenance mode.
*
* Creates the handler symlink and sentinel file with current timestamp.
* Safe to call when maintenance mode is already active (timestamp will be updated).
*
* ATTENTION: after activation wp-admin and wp-login.php will be inaccessible until maintenance mode is deactivated.
* delete wp-content/maintenance.php and wp-content/.stretch-extra-maintenance to reset maintenance mode.
*/

ionos\stretch_extra\maintenance\activate();
