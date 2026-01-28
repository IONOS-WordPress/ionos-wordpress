<?php

/*
  resets all options related to the IONOS Essenatials Dashboard
 */

\delete_option('ionos_nba_status');
\delete_option('ionos_essentials_nba_setup_completed');
\delete_option('ionos_essentials_loop_nba_actions_shown');

echo 'succeeded';
