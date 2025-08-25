<?php

namespace ionos\essentials\loop\Data;

/**
 * Storage class for custom data.
 */
class CustomDataStore
{
  /**
   * The Options key in the wp_options.
   *
   * @var string
   */
  private $options_key;

  /**
   * The unprefixed version of the options key.
   *
   * @var string
   */
  private $store_key;

  /**
   * The Custom Data Store constructor.
   *
   * Notice: You cannot have multi
   *
   * @param string  $options_key   The options key for the wp-options.
   * @param boolean $auto_register Shall this custom option be automatically registered to the rest API output? Default: true.
   * @param boolean $register_actions Shall this custom automatically register actions? Default: false.
   */
  public function __construct($options_key, $auto_register = true, $register_actions = false)
  {
    $this->store_key   = $options_key;
    $this->options_key = 'ionos_loop_' . $options_key;

    if ($auto_register === true) {
      $this->register();
    }

    if ($register_actions === true) {
      $this->register_actions();
    }
  }

  /**
   * Loads the option and possibly resets it with an empty array.
   */
  public function load_option()
  {
    $option = get_option($this->options_key, []);
    if (! is_array($option)) {
      $option = [];
      $this->save($option);
    }

    return $option;
  }

  /**
   * Registers this data store to the Rest API Output.
   */
  public function register()
  {
    $stats = get_option('ionos_loop', []);
    if (! in_array($this->store_key, $stats, true)) {
      $stats[] = $this->store_key;
      update_option('ionos_loop', $stats);
    }
  }

  /**
   * Registers actions so that functions can be called via actions.
   *
   * Naming schema: ionos_loop_{$key}_{$method_name}
   *
   * With $method_name having the following values:
   *
   * Name   Parameters         Description
   * add    $array_key, $data  Pushes an entry to an array at the given key.
   * update $array_key, $data  Writes (and overwrites) data at the given key.
   * remove $array_key         Deletes the entry at the given key.
   * delete none               Deletes the whole option data.
   */
  public function register_actions()
  {
    $methods_to_actions = [
      'add'    => 2,
      'update' => 2,
      'remove' => 1,
      'delete' => 0,
    ];

    foreach ($methods_to_actions as $method_name => $param_count) {
      add_action("ionos_loop_{$this->store_key}_{$method_name}", [$this, $method_name], 10, $param_count);
    }
  }

  /**
   * Unregisters this data store from the Rest API Output.
   */
  public function unregister()
  {
    $stats = get_option('ionos_loop', []);
    $stats = array_diff($stats, [$this->options_key]);
    update_option('ionos_loop', $stats);
  }

  /**
   * Saves the data.
   *
   * @param array $option The data to store.
   */
  public function save($option)
  {
    update_option($this->options_key, $option);
  }

  /**
   * Returns the data.
   *
   * @return array
   */
  public function get()
  {
    return $this->load_option();
  }

  /**
   * Pushes the data to an array at the given key.
   *
   * @param string $key  The array key.
   * @param mixed  $data The data to store.
   */
  public function add($key, $data)
  {
    if (! is_string($key)) {
      _doing_it_wrong(__METHOD__, 'Expects parameters 1 to be strings, this is the array keys', '0.0.1');
      return;
    }

    $option           = $this->load_option();
    $option[$key][]   = $data;
    $this->save($option);
  }

  /**
   * Stores(and possibly overwrites) the data at the given array key.
   *
   * @param string $key  The array key.
   * @param mixed  $data The data to store.
   */
  public function update($key, $data)
  {
    if (! is_string($key)) {
      _doing_it_wrong(__METHOD__, 'Expects parameters 1 to be strings, this is the array keys', '0.0.1');
      return;
    }

    $option         = $this->load_option();
    $option[$key]   = $data;
    $this->save($option);
  }

  /**
   * Deletes an entry at the given array key.
   *
   * @param string $key The array key.
   */
  public function remove($key)
  {
    if (! is_string($key)) {
      _doing_it_wrong(__METHOD__, 'Expects parameters 1 to be strings, this is the array keys', '0.0.1');
      return;
    }

    $option = $this->load_option();
    unset($option[$key]);
    $this->save($option);
  }

  /**
   * Deletes the complete data.
   */
  public function delete()
  {
    delete_option($this->options_key);
  }
}
