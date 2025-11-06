<?php

namespace ionos\essentials\switch_page;

use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

$configs = [
  'ionos' => [
    'color' => '#0b2a63',
    'logo'  => 'ionos.svg',
  ],
  'fasthosts' => [
    'color' => '#031A4A',
    'logo'  => 'fasthosts.svg',
  ],
  'arsys' => [
    'color' => '#000016',
    'logo'  => 'arsys.svg',
  ],
  'piensa' => [
    'color' => '#010101',
    'logo'  => 'piensa.svg',
  ],
  'strato' => [
    'color' => '#FF8800',
    'logo'  => 'strato.svg',
  ],
  'udag' => [
    'color' => '#092850',
    'logo'  => 'udag.svg',
  ],
  'homepl' => [
    'color' => '#000000',
    'logo'  => 'homepl.svg',
  ],
];

$tenant = Tenant::get_slug();
$config = $configs[$tenant] ?? reset($configs);
$theme_file = realpath(__DIR__ . '/../dashboard/exos-themes/' . $tenant . '.css');

if (file_exists($theme_file)) {
  \wp_register_style(
    handle: 'exos-theme',
    src: \plugins_url($tenant . '.css', $theme_file),
    ver: filemtime($theme_file)
  );
  \wp_print_styles(['exos-theme']);

  wp_deregister_style( 'buttons' );

}

?>

<div class="wrapper page-section">
  <div class="header">
    <img class="logo" src="<?php echo \esc_attr(
      \plugins_url('assets/logos/' . $config['logo'], __FILE__)
    ); ?>" alt="<?php \esc_attr_e('Logo', 'ionos-essentials'); ?>"/>
  </div>
  <div class="onboardingpage">
    <h1 class="headline"><?php \esc_html_e('How do you want to create your new website?', 'ionos-essentials'); ?></h1>
    <div class="container">
      <div class="options">
        <div class="option card">
          <div class="option-content card__section">
            <img src="<?php echo \esc_url(
              \plugins_url('assets/machine-learning.svg', __FILE__)
            ); ?>" alt="<?php \esc_attr_e('Artificial Intelligence Illustration', 'ionos-essentials'); ?>"/>
            <h3 class="headline"><?php \esc_html_e('With AI', 'ionos-essentials'); ?></h3>
            <p class="paragraph"><?php \esc_html_e(
              'A native WordPress website in minutes. Our powerful AI generates design, images and content based on your needs.',
              'ionos-essentials'
            ); ?></p>
            <a href="?php echo \esc_attr(\admin_url('admin.php?page=extendify-launch')); ?>" class="button button--primary"><?php \esc_html_e('Continue with AI', 'ionos-essentials'); ?></a>
          </div>
        </div>
        <div class="option card">
          <div class="option-content card__section">
            <img
              src="<?php echo \esc_attr(\plugins_url('assets/user-interface.svg', __FILE__)); ?>"
              alt="<?php \esc_attr_e('User Interface Illustration', 'ionos-essentials'); ?>"
            />
            <h3 class="headline"><?php \esc_html_e('Do it yourself', 'ionos-essentials'); ?></h3>
            <p class="paragraph"><?php \esc_html_e(
              'Take full control of the creative process. You can easily switch over to the AI builder later from inside Wordpress.',
              'ionos-essentials'
            ); ?></p>
            <a href="<?php echo \esc_attr(
          \admin_url('admin.php?page=' . Tenant::get_slug())
        ); ?>" class="button button--secondary"><?php \esc_html_e('Create manually', 'ionos-essentials'); ?></a>
          </div>
        </div>
      </div>

      <div class="checkbox-option">
        <span>
          <?php
          printf(
            // translators: %1$s, %2$s, %3$s and %4$s are placeholders for html tags.
            \esc_html__(
              'By using AI features you agree to the OpenAI %1$sTerms of Use%2$s and %3$sPrivacy Policy%4$s',
              'ionos-essentials'
            ),
            '<a href="https://openai.com/policies/terms-of-use/" target="_blank">',
            '</a>',
            '<a href="https://openai.com/policies/privacy-policy/" target="_blank">',
            '</a>'
          );
?>
        </span>
      </div>
    </div>
  </div>
</div>
