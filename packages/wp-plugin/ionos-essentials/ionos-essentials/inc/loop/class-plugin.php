<?php

namespace ionos\essentials\loop;

defined('ABSPATH') || exit();

use ionos\essentials\loop\data\CustomDataStore;

class Plugin
{
  public static function init()
  {
    \add_action('ionos_loop_init_custom_store', [__CLASS__, 'register_custom_data_store_action']);
  }


  /**
   * Registers a custom data store via action.
   *
   * @param string $options_key The action key.
   */
  public static function register_custom_data_store_action($options_key)
  {
    static $instances = [];

    if (! isset($instances[$options_key]) || ! is_a($instances[$options_key], CustomDataStore::class)) {
      $instances[$options_key] = new CustomDataStore($options_key, true, true);
    }
  }

  /**
   * Revokes the consent and deletes all data
   */
  public static function revoke_consent()
  {
    foreach (wp_load_alloptions() as $key => $value) {
      if (strpos($key, 'ionos_loop_') !== false) {
        delete_option($key);
      }
    }
  }
}
