<?php

namespace ionos\ionos_core;

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
class MU_Plugin_Upgrader extends \Plugin_Upgrader
{
    protected $slug;

    public function __construct($slug)
    {
      parent::__construct();
        $this->slug = $slug;
    }

    protected function get_delete_post_link( ){
      return WP_PLUGIN_URL . '/' . $this->slug;
    }
}

