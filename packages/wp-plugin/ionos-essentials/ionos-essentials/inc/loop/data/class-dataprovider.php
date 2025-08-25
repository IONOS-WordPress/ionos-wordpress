<?php

namespace ionos\essentials\loop\Data;

/**
 * Abstract class for Data Providers in the Rest API.
 */
abstract class DataProvider
{
  /**
   * The data for the data provider.
   *
   * @var string
   */
  private $data;

  /**
   * Constructor, calls the collect function and gets all data ready.
   */
  public function __construct()
  {
    $this->set_data($this->collect_data());
  }

  /**
   * Returns the data.
   *
   * @return string
   */
  public function get_data()
  {
    return $this->data;
  }

  /**
   * Stores the provided data.
   *
   * @param array $data Data which was collected.
   */
  protected function set_data($data)
  {
    $this->data = $data;
  }

  /**
   * Collects the required data, and returns these.
   *
   * @return array
   */
  abstract protected function collect_data();
}
