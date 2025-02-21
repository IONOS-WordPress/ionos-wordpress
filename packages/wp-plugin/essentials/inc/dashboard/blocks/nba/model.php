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

  private function __construct(
    readonly string $id,
    readonly string $title,
    readonly string $description,
    readonly string $link,
    readonly bool $completed
  ) {
    $a = "b";
    self::$actions[$this->id] = $this;
  }

  public function __get($property)
  {
    if ('active' === $property) {
      $status = $this->_get_status();
      if (isset($status["completed"]) && $status["completed"] || isset($status["dismissed"]) && $status["dismissed"]) {
        return false;
      }
      // $status = (object) $this->_get_status();
      // if ($status?->completed ?? false || $status?->dismissed ?? false) {
      //   return false;
      // }
      return ! $this->completed;
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

  public static function register($id, $title, $description, $link, $completed = false)
  {
    new NBA($id, $title, $description, $link, $completed);
  }

}

for ($i = 1; $i <= 20; $i++) {
    NBA::register(
      id: 'checkPluginsPage' . $i,
      title: 'NBA' . $i,
      description: 'Description of NBA' . $i,
      link: admin_url('plugins.php?complete_nba=checkPluginsPage' . $i),
      // completed: (function(){
      //   return false;
      // })()
    );
}
