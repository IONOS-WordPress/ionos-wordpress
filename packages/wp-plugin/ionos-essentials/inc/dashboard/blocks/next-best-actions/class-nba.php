<?php

/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos\essentials\dashboard\blocks\next_best_actions;

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
    readonly string $anchor,
    private readonly bool $completed
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

  public static function register($id, $title, $description, $link, $anchor, $completed = false): void
  {
    new self($id, $title, $description, $link, $anchor, $completed);
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

$data = \ionos\essentials\dashboard\blocks\my_account\get_account_data();

if (null !== $data) {

  $connectdomain = $data['nba_links']['connectdomain'] ?? '';

  NBA::register(
    id: 'connect-domain',
    title: \__('Connect a Domain', 'ionos-essentials'),
    description: \__(
      'Connect your domain to your website to increase visibility and attract more visitors.',
      'ionos-essentials'
    ),
    link: $data['domain'] . $connectdomain,
    anchor: \__('Connect Domain', 'ionos-essentials'),
    completed: false === strpos(home_url(), 'live-website.com') && false === strpos(home_url(), 'localhost'),
  );
}

NBA::register(
  id: 'edit-and-complete',
  title: \__('Edit & Complete Your Website', 'ionos-essentials'),
  description: \__(
    'Add pages, text, and images, fine-tune your website with AI-powered tools or adjust colours and fonts.',
    'ionos-essentials'
  ),
  link: \admin_url('post-new.php?post_type=page&ext-close'), //  /wp-admin/post-new.php?post_type=page&ext-close
  anchor: \__('Edit Website', 'ionos-essentials'),
  completed: 1 < \wp_count_posts('page')
    ->publish
);

if (is_plugin_active('extendify/extendify.php')) {
  NBA::register(
    id: 'help-center',
    title: \__('Discover Help Center', 'ionos-essentials'),
    description: \__(
      'Get instant support with Co-Pilot AI, explore our Knowledge Base, or take guided tours.',
      'ionos-essentials'
    ),
    link: '#',
    anchor: \__('Open Help Center', 'ionos-essentials'),
    completed: false // handled by view.js
  );
}

if (null !== $data) {
  if (false !== strpos(home_url(), 'live-website.com') && (false !== strpos(home_url(), 'localhost'))) {
    $connectmail = $data['nba_links']['connectmail'] ?? '';

    NBA::register(
      id: 'email-account',
      title: \__('Set Up Email', 'ionos-essentials'),
      description: \__(
        'Set up your included email account and integrate it with your website.',
        'ionos-essentials'
      ),
      link: $data['domain'] . $connectmail,
      anchor: \__('Setup Email Account', 'ionos-essentials'),
      completed: false // handled by view.js because of external link
    );
  }
}

if (! function_exists('is_plugin_active')) {
  include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// show when contactform7 is installed and active
if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
  NBA::register(
    id: 'contact-form',
    title: \__('Set Up Contact Form', 'ionos-essentials'),
    description: \__('Create a contact form to stay connected with your visitors.', 'ionos-essentials'),
    link: \admin_url('admin.php?page=wpcf7-new&complete_nba=contact-form'),
    anchor: \__('Set Up Contact Form', 'ionos-essentials'),
    completed: false,
  );
}

// show when woocommerce is installed and active
if (is_plugin_active('woocommerce/woocommerce.php')) {
  $woo_onboarding_status = get_option('woocommerce_onboarding_profile');

  NBA::register(
    id: 'woocommerce',
    title: \__('Set Up Your WooCommerce Store', 'ionos-essentials'),
    description: \__('Launch your online store now with a guided setup wizard.', 'ionos-essentials'),
    link: \admin_url('admin.php?page=wc-admin&path=%2Fsetup-wizard'),
    anchor: \__('Start Setup', 'ionos-essentials'),
    completed: isset($woo_onboarding_status['completed']) || isset($woo_onboarding_status['skipped']), // when setup completed or cta is clicked
  );
}

$tenant        = strtolower(\get_option('ionos_group_brand', 'ionos'));
$market        = strtolower(\get_option($tenant . '_market', 'de'));

if ('de' === $market && is_plugin_active('woocommerce/woocommerce.php') && ! is_plugin_active(
  'woocommerce-german-market-light/woocommerce-german-market-light.php'
)) {
  NBA::register(
    id: 'woocommerce-gml',
    title: \__('Legally compliant selling with German Market Light', 'ionos-essentials'),
    description: \__('Use the free extension for WooCommerce to operate your online store in Germany and Austria in a legally compliant manner.', 'ionos-essentials'),
    link: '#',
    anchor: \__('Install now', 'ionos-essentials'),
    completed: is_plugin_active(
      'woocommerce-german-market-light/WooCommerce-German-Market-Light.php'
    ), // when gml is installed and activate
  );
}

if ('extendable' === get_stylesheet()) {
  $custom_logo_id           = get_theme_mod('custom_logo');
  $logo                     = wp_get_attachment_image_src($custom_logo_id, 'full');
  $logo_src                 = $logo ? $logo[0] : '';
  $is_default_or_empty_logo = false !== strpos($logo_src, 'extendify-demo-logo.png') || '' === $logo_src;

  NBA::register(
    id: 'upload-logo',
    title: \__('Add Logo', 'ionos-essentials'),
    description: \__(
      'Ensure your website is branded with your unique logo for a professional look.',
      'ionos-essentials'
    ),
    link: \admin_url(
      'site-editor.php?postId=extendable%2F%2Fheader&postType=wp_template_part&focusMode=true&canvas=edit&essentials-nba=true'
    ),
    anchor: \__('Add Logo', 'ionos-essentials'),
    completed: ! $is_default_or_empty_logo
  );
}

NBA::register(
  id: 'create-page',
  title: \__('Create a Page', 'ionos-essentials'),
  description: \__('Create and publish a page and share your story with the world.', 'ionos-essentials'),
  link: \admin_url('post-new.php?post_type=page&complete_nba=create-page'),
  anchor: \__('Create Page', 'ionos-essentials'),
  completed: false
);

if ('extendable' === get_stylesheet()) {
  NBA::register(
    id: 'social-media',
    title: \__('Social Media Setup', 'ionos-essentials'),
    description: \__(
      'Connect your social media profiles to your website and expand your online presence.',
      'ionos-essentials'
    ),
    link: \admin_url(
      'site-editor.php?postId=extendable%2F%2Ffooter&postType=wp_template_part&focusMode=true&canvas=edit&complete_nba=social-media'
    ),
    anchor: \__('Connect Social Media', 'ionos-essentials'),
    completed: false
  );
}

NBA::register(
  id: 'favicon',
  title: \__('Add Favicon', 'ionos-essentials'),
  description: \__(
    'Add a favicon (website icon) to your website to enhance brand recognition and visibility.',
    'ionos-essentials'
  ),
  link: \admin_url('options-general.php'),
  anchor: \__('Add Favicon', 'ionos-essentials'),
  completed: 0 < intval(\get_option('site_icon', 0))
);

// for($i = 1; $i <= 12; $i++) {
//   NBA::register(
//     id: 'dosth' . $i,
//     title: 'Do something ' . $i,
//     description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
//     link: '#',
//     anchor: 'Hier klicken',
//     completed: false
//   );
// }
