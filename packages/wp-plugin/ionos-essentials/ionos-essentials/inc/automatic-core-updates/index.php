<?php

namespace ionos\essentials\automatic_updates;

defined('ABSPATH') || exit();

\add_action(
  hook_name: 'admin_head-update-core.php',
  callback: function (): void {
    \printf(
      <<<HTML
      <style>
        .auto-update-status{
          display: none;
        }
      </style>
      HTML
    );
  }
);
