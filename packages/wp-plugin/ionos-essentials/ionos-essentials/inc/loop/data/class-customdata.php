<?php

namespace ionos\essentials\loop\Data;

/**
 * Custom data provider for information stored with Custom_Data_Store
 */
class CustomData extends DataProvider
{
  /**
   * The options key.
   *
   * @var string
   */
  private $options_key;

  /**
   * @param string $options_key The options key.
   */
  public function __construct($options_key)
  {
    $this->options_key = 'ionos_loop_' . $options_key;

    parent::__construct();
  }

  /**
   * The data collector, reads all the stored data from the wp_options.
   *
   * @return array
   */
  protected function collect_data()
  {
    $data = get_option($this->options_key, []);

    if (! is_array($data)) {
      return [];
    }

    return $data;
  }
}
