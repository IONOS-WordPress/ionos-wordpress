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
    readonly string $id,
    readonly string $title,
    readonly string $link,
    readonly mixed $completed_callback
  ) {
    $a = "b";
    self::$actions[$this->id] = $this;
  }

  public function __get($property)
  {
    if ('completed' === $property) {
      if (is_bool($this->completed_callback)) {
        return $this->completed_callback;
      } else {
        $status = $this->_get_status();
        if (isset($status["completed"]) && $status["completed"]) {
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

  private static function _set_option(array $option)
  {
    return \update_option(self::$option_name, $option);
  }

  private function _get_status()
  {
    $option = $this->_get_option();
    return $option[$this->id] ?? [];
  }

  function setStatus($key, $value) {
    $id = $this->id;
    $option = self::_get_option();

    $option[$id] ??= [];
    $option[$id][$key] = $value;
    return self::_set_option($option);
  }

  public static function getNBA($id)
  {
    return self::$actions[$id];
  }

  public static function getActions()
  {
    return self::$actions;
  }

  public static function create($id, $title, $link, $completed_callback)
  {
    return new NBA($id, $title, $link, $completed_callback);
  }

}

for ($i = 1; $i <= 20; $i++) {
    NBA::create(
      id: 'checkPluginsPage' . $i,
      title: 'NBA' . $i,
      link: admin_url('plugins.php?complete_nba=checkPluginsPage' . $i),
      completed_callback: fn () => false && !!random_int(0, 1)
    );
}
