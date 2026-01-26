<?php

/*
  reset the essentials dashboard welcome screen
*/

printf(
  "deleted user meta 'ionos_essentials_welcome' : %s",
  \delete_user_meta(1, 'ionos_essentials_welcome') ? 'success' : 'was not set'
);
