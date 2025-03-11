<?php

/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions;

enum ActionStatus
{
  case completed;
  case dismissed;
}

class NBA
{
  public const OPTION_NAME = 'ionos_nba_status';

  private static $option_value;

  private static array $actions = [];

  private function __construct(
    readonly string $id,
    readonly string $title,
    readonly string $description,
    readonly string $link,
    private readonly bool $completed
  ) {
    self::$actions[$this->id] = $this;
  }

  public function __get($property)
  {
    // actions can be active (and thus shown to the user) for multiple reasons: they are not completed, they are not dismissed, they are not active yet, ...
    if ('active' === $property) {
      $status = $this->_get_status();
      if (isset($status['completed']) && $status['completed'] || isset($status['dismissed']) && $status['dismissed']) {
        return false;
      }
      return ! $this->completed;
    }
  }

  public function setStatus(ActionStatus $key, $value)
  {
    $id     = $this->id;
    $option = self::_get_option();

    $option[$id] ??= [];
    $option[$id][$key->name] = $value;
    return self::_set_option($option);
  }

  public static function getNBA($id): self|null
  {
    return self::$actions[$id];
  }

  public static function getActions(): array
  {
    return self::$actions;
  }

  public static function register($id, $title, $description, $link, $completed = false): void
  {
    new self($id, $title, $description, $link, $completed);
  }

  private static function _get_option()
  {
    if (! isset(self::$option_value)) {
      self::$option_value = \get_option(self::OPTION_NAME, []);
    }
    return self::$option_value;
  }

  private static function _set_option(array $option)
  {
    self::$option_value = $option;
    return \update_option(self::OPTION_NAME, $option);
  }

  private function _get_status()
  {
    $option = $this->_get_option();
    return $option[$this->id] ?? [];
  }
}

NBA::register(
  id: 'add-page',
  title: \esc_html__('Add a page', 'ionos-essentials'),
  description: \esc_html__('Create some content for your website visitor.', 'ionos-essentials'),
  link: \admin_url('post-new.php?post_type=page'),
  completed: 1 < \wp_count_posts('page')
    ->publish
);
NBA::register(
  id: 'add-post',
  title: \esc_html__('Add a post', 'ionos-essentials'),
  description: \esc_html__('Share your thoughts with your audience.', 'ionos-essentials'),
  link: \admin_url('edit.php?post_type=post'),
  completed: 1 < \wp_count_posts('post')
    ->publish
);
NBA::register(
  id: 'edit-post',
  title: \esc_html__('Edit a post', 'ionos-essentials'),
  description: \esc_html__('Update your content to keep it fresh.', 'ionos-essentials'),
  link: \admin_url('edit.php?post_type=post'),
  completed: false
);
NBA::register(
  id: 'edit-page',
  title: \esc_html__('Edit a page', 'ionos-essentials'),
  description: \esc_html__('Update your content to keep it fresh.', 'ionos-essentials'),
  link: \admin_url('edit.php?post_type=page'),
  completed: false
);
NBA::register(
  id: 'add-site-description',
  description: \esc_html__('Tell your visitors what your website is about.', 'ionos-essentials'),
  title: \esc_html__('Add a site description', 'ionos-essentials'),
  link: \admin_url('options-general.php'),
  completed: '' !== \get_option('blogdescription') && __('Just another WordPress site') !== \get_option(
    'blogdescription'
  )
);
NBA::register(
  id: 'upload-logo',
  title: \esc_html__('Upload a logo', 'ionos-essentials'),
  description: \esc_html__('Make your website more recognizable.', 'ionos-essentials'),
  link: \admin_url('options-general.php'),
  completed: 0 < intval(\get_option('site_icon', 0))
);

\do_action('ionos_dashboard__register_nba_element');
