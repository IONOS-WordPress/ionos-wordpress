<?php

/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model;

class NBA
{
  private static $option_name = 'ionos_nba_status';

  private static $option_value;
  private static array $actions = [];

  public function __construct(
    private readonly string $id,
    readonly string $title,
    readonly string $link,
    readonly mixed $completed_callback
  ) {
    $foo  = 'bar';
    self::$actions[$this->id] = $this;
  }

  public function __get($property)
  {
    if ('completed' === $property) {
      if (is_bool($this->completed_callback)) {
        return $this->completed_callback;
      } else {
        $status = $this->_get_status();
        if ($status && $status["completed"]) {
          return true;
        }
      }
      return call_user_func($this->completed_callback);
    }
  }

  private static function _get_option()
  {
    if (! isset(self::$option_value)) {
      self::$option_value = \get_option(self::$option_name, []);
    }
    return self::$option_value;
  }

  private static function _set_option()
  {
    \update_option(self::$option_name, self::$option_value);
  }

  private function _get_status()
  {
    $option = $this->_get_option();
    return $option[$this->id] ?? false;
  }

  public static function complete($id)
  {
    $option = self::_get_option();
    if (isset($option[$id])) {
      $option[$id]["completed"] = true;
    } else {
      $option[$id] = ["completed" => true];
    };
    self::$option_value = $option;
    self::_set_option();
  }

  public static function getNBA($id)
  {
    return self::$actions[$id];
  }
}
