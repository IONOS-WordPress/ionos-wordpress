<?php

/*
  sends a email
 */

\add_action('wp_mail_failed', 'wp_mail_failed_handler');
function wp_mail_failed_handler($wp_error)
{
  print_r($wp_error->get_error_messages());
}

printf('test send a email : %s', \wp_mail('recipient@example.com', 'Subject', 'Message') ? 'succeed' : 'failed');
