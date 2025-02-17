<?php

/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model;

class NBA
{
  private static $option_name = 'ionos_nba_status';

  private static $option_value;

  public function __construct(
    private readonly string $id,
    readonly string $title,
    readonly string $link,
    callable|bool $completed_callback
  ) {
  }

  public function __get($property)
  {
    if ('completed' === $property) {
      if (is_bool($this->completed_callback)) {
        return $this->completed_callback;
      }
      return ! ! random_int(0, 1);
      // return call_user_func($this->completed_callback);
    }
  }

  private static function _get_option()
  {
    if (! isset(self::$option_value)) {
      self::$option_value = \get_option(self::$option_name, []);
    }
    return self::$option_value;
  }

  private function _get_status()
  {
    $option = $this->_get_option();
    return $option[$this->id] ?? false;
  }
}
