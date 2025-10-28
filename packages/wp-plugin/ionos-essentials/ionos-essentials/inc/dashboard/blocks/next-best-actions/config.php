<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

$data = \ionos\essentials\dashboard\blocks\my_account\get_account_data();

$homepage = \get_option('page_on_front'); // returns "0" if no static front page is set
$edit_url = intval($homepage) === 0 ? \admin_url('edit.php?post_type=page') : admin_url(
  'post.php?post=' . $homepage . '&action=edit'
);
NBA::register(
  id: 'edit-and-complete',
  title: \__('Edit & Complete Your Website', 'ionos-essentials'),
  description: \__(
    'Add pages, text, and images, fine-tune your website with AI-powered tools or adjust colours and fonts.',
    'ionos-essentials'
  ),
  link: $edit_url,
  anchor: \__('Edit Website', 'ionos-essentials'),
  complete_on_click: true,
  categories: ['setup-ai']
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
    complete_on_click: true,
    categories: ['after-setup']
  );
}

if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
  NBA::register(
    id: 'contact-form',
    title: \__('Set Up Contact Form', 'ionos-essentials'),
    description: \__('Create a contact form to stay connected with your visitors.', 'ionos-essentials'),
    link: \admin_url('admin.php?page=wpcf7-new'),
    anchor: \__('Set Up Contact Form', 'ionos-essentials'),
    complete_on_click: true,
    categories: ['setup-ai']
  );
}

if (is_plugin_active('woocommerce/woocommerce.php')) {
  $woo_onboarding_status = get_option('woocommerce_onboarding_profile');

  NBA::register(
    id: 'woocommerce',
    title: \__('Set Up Your WooCommerce Store', 'ionos-essentials'),
    description: \__('Launch your online store now with a guided setup wizard.', 'ionos-essentials'),
    link: \admin_url('admin.php?page=wc-admin&path=%2Fsetup-wizard'),
    anchor: \__('Start Setup', 'ionos-essentials'),
    completed: isset($woo_onboarding_status['completed']) || isset($woo_onboarding_status['skipped']), // when setup completed or cta is clicked
    categories: ['setup-ai']
  );
}

NBA::register(
  id: 'select-theme',
  title: \__('Select a Theme', 'ionos-essentials'),
  description: \__(
    'Choose a theme that matches your website\'s purpose and your comfort level.',
    'ionos-essentials'
  ),
  link: \admin_url('themes.php'),
  anchor: \__('Select a Theme', 'ionos-essentials'),
  completed: \wp_get_theme()
    ->get_stylesheet() !== 'extendable',
  categories: ['setup-noai']
);

NBA::register(
  id: 'create-page',
  title: \__('Create a Page', 'ionos-essentials'),
  description: \__('Create and publish a page and share your story with the world.', 'ionos-essentials'),
  link: \admin_url('post-new.php?post_type=page'),
  anchor: \__('Create Page', 'ionos-essentials'),
  complete_on_click: true,
  categories: ['setup-noai']
);

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
    completed: false === strpos(home_url(), 'live-website.com'),
    categories: ['setup-ai', 'setup-noai']
  );

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
      complete_on_click: true,
      categories: ['after-setup']
    );
  }
}

$tenant        = Tenant::get_slug();
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
    categories: ['after-setup']
  );
}

if ('extendable' === get_stylesheet()) {
  NBA::register(
    id: 'social-media',
    title: \__('Social Media Setup', 'ionos-essentials'),
    description: \__(
      'Connect your social media profiles to your website and expand your online presence.',
      'ionos-essentials'
    ),
    link: \admin_url(
      'site-editor.php?postId=extendable%2F%2Ffooter&postType=\wp_template_part&focusMode=true&canvas=edit'
    ),
    anchor: \__('Connect Social Media', 'ionos-essentials'),
    complete_on_click: true,
    categories: ['after-setup']
  );

  $custom_logo_id           = get_theme_mod('custom_logo');
  $logo                     = \wp_get_attachment_image_src($custom_logo_id, 'full');
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
      'site-editor.php?postId=extendable%2F%2Fheader&postType=\wp_template_part&focusMode=true&canvas=edit&essentials-nba=true'
    ),
    anchor: \__('Add Logo', 'ionos-essentials'),
    completed: ! $is_default_or_empty_logo,
    categories: ['after-setup']
  );
}

NBA::register(
  id: 'favicon',
  title: \__('Add Favicon', 'ionos-essentials'),
  description: \__(
    'Add a favicon (site icon) to your website to enhance brand recognition and visibility.',
    'ionos-essentials'
  ),
  link: \admin_url('options-general.php'),
  anchor: \__('Add Favicon', 'ionos-essentials'),
  completed: 0 < intval(\get_option('site_icon', 0)),
  categories: ['after-setup']
);

$contact_query = new \WP_Query([
  'post_type'      => 'page',
  'title'          => __('contact', 'extendify-local'),
  'posts_per_page' => 1,
  'fields'         => 'ids',
  'meta_query'     => [
    [
      'key'     => 'made_with_extendify_launch',
      'compare' => '1',
    ],
  ],
]);
$contact_post_id = ! empty($contact_query->posts) ? $contact_query->posts[0] : 0;
if ($contact_post_id) {
  NBA::register(
    id: 'personalize-business-data',
    title: \__('Personalize business data', 'ionos-essentials'),
    description: \__(
      'Add your business details, like a phone number, email, and address, to your website.',
      'ionos-essentials'
    ),
    link: \admin_url('post.php?post=' . $contact_post_id . '&action=edit'),
    anchor: \__('Personalize business data', 'ionos-essentials'),
    complete_on_click: true,
    categories: ['setup-ai']
  );
}

NBA::register(
  id: 'tools-and-security',
  title: \__('\'Tools & Security\' area', 'ionos-essentials'),
  description: \__("All the features from your previous security plugin have now found their new home here. Plus, you'll find a new maintenance page function that you can switch on whenever you need it.", 'ionos-essentials'),
  link: '#tools',
  anchor: \__('Visit Tools & Security', 'ionos-essentials'),
  complete_on_click: true,
  categories: ['always'],
  exos_icon: 'megaphone',
  expanded: true
);

if ('ionos' === Tenant::get_slug()) {
  NBA::register(
    id: 'survey',
    title: \__('Help us shape WordPress for you', 'ionos-essentials'),
    description: \__("We're always looking for ways to make your WordPress hosting experience even better. Please take a few minutes to fill out a quick online survey.", 'ionos-essentials'),
    link: get_survey_url(),
    anchor: \__('Take the survey', 'ionos-essentials'),
    complete_on_click: true,
    categories: ['always'],
    exos_icon: 'conversation',
    expanded: true
  );
}
