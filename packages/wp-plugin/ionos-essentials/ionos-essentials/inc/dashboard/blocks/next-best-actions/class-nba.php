<?php

/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

class NBA
{
  public const OPTION_STATUS_NAME = 'ionos_nba_status';

  private static $option_value;

  private static array $actions = [];

  private function __construct(
    public readonly string $id,
    public readonly string $title,
    public readonly string $description,
    public readonly string $link,
    public readonly string $anchor,
    private readonly bool $completed,
    public readonly bool $complete_on_click,
    public readonly array $categories,
    public readonly string $exos_icon,
    public readonly bool $expanded
  ) {
    self::$actions[$this->id] = $this;
  }

  public function __get($property)
  {
    // actions can be active (and thus shown to the user) for multiple reasons: they are not completed, they are not dismissed, they are not active yet, ...
    if ('active' === $property) {
      $status = $this->_get_status();
      if (($status['completed'] ?? false) || ($status['dismissed'] ?? false)) {
        return false;
      }
      return ! $this->completed;
    }
  }

  public function set_status($key, $value)
  {
    $id     = $this->id;
    $option = self::_get_option();

    $option[$id] ??= [];
    $option[$id][$key] = $value;
    return self::_set_option($option);
  }

  public static function get_nba($id): self|null
  {
    return self::$actions[$id];
  }

  public static function get_actions(): array
  {
    return self::$actions;
  }

  public static function register(
    string $id,
    string $title,
    string $description,
    string $link,
    string $anchor,
    bool $completed = false,
    bool $complete_on_click = false,
    array $categories = [],
    string $exos_icon = 'target',
    bool $expanded = false
  ): void {
    new self(
      $id,
      $title,
      $description,
      $link,
      $anchor,
      $completed,
      $complete_on_click,
      $categories,
      $exos_icon,
      $expanded
    );
  }

  private static function _get_option()
  {
    if (! isset(self::$option_value)) {
      self::$option_value = \get_option(self::OPTION_STATUS_NAME, []);
    }
    return self::$option_value;
  }

  private static function _set_option(array $option)
  {
    self::$option_value = $option;
    return \update_option(self::OPTION_STATUS_NAME, $option);
  }

  private function _get_status()
  {
    $option = $this->_get_option();
    return $option[$this->id] ?? [];
  }
}

use const ionos\essentials\PLUGIN_DIR;

require_once PLUGIN_DIR . '/ionos-essentials/inc/dashboard/blocks/next-best-actions/config.php';

if (! function_exists('is_plugin_active')) {
  include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

function get_survey_url(): string
{
  $survey_links = [
    'de'    => 'https://feedback.ionos.com/nmdopgnfds?l=de',
    'en_us' => 'https://feedback.ionos.com/nmdopgnfds?l=en-us',
    'en'    => 'https://feedback.ionos.com/nmdopgnfds?l=en',
    'fr'    => 'https://feedback.ionos.com/nmdopgnfds?l=fr',
    'es'    => 'https://feedback.ionos.com/nmdopgnfds?l=es',
    'it'    => 'https://feedback.ionos.com/nmdopgnfds?l=it',
  ];
  $locale = determine_locale();
  if ($locale === 'en_US') {
    return $survey_links['en_us'];
  }
  $lang = strtolower(preg_split('/[_-]/', $locale)[0]);
  if (isset($survey_links[$lang])) {
    return $survey_links[$lang];
  }
  return $survey_links['en'];
}
