<?php
/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model;

/**
 * Class NBA
 */
final class NBA
{
  const WP_OPTION_NAME='NBA_OPTION';

  static protected array $_wp_option;

  function __construct(
    readonly string $id,
    readonly string $title,
    readonly string $description,
    readonly string $image,
    readonly mixed $callback,
    readonly bool $completed = false,
    readonly bool $dismissed = false
    )
  {}

  function __set(string $optionName, mixed $value): void {
    match ($optionName) {
      'completed' => static::_setOption($this->id, 'completed', $value),
      'dismissed' => static::_setOption($this->id, 'dismissed', $value),
      default => throw new \InvalidArgumentException("Invalid property: $optionName"),
    };
  }

  function __get(string $optionName): mixed {
    return match ($optionName) {
      'id' => $this->id,
      'title' => $this->title,
      'description' => $this->description,
      'image' => $this->image,
      'callback' => is_callable($this->callback) ? call_user_func($this->callback) : null,
      'completed' => static::_getOption($this->id, 'completed'),
      'dismissed' => static::_getOption($this->id, 'dismissed'),
      default => throw new \InvalidArgumentException("Invalid property: $optionName"),
    };
  }

  protected static function _getWPOption() {
      $option = \get_option(static::WP_OPTION_NAME);
      return is_array( $option ) ? $option : [];
  }


  protected static function _setOption(string $id, string $optionName, string $value) {
    static::$_wp_option = static::_getWPOption();
    static::$_wp_option[$id][$optionName] = $value;
    \update_option(static::WP_OPTION_NAME, static::$_wp_option);
  }

  protected static function _getOption(string $id, string $optionName): mixed {
    static::$_wp_option = static::_getWPOption();
    return static::$_wp_option[$id][$optionName] ?? null;
  }
}
