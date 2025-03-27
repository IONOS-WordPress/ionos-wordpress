<?php

namespace ionos_wordpress\essentials\switch_page;

if (! defined('ABSPATH')) {
  exit;
}

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
];

$tenant = strtolower(\get_option('ionos_group_brand', 'ionos'));
$config = $configs[$tenant] ?? reset($configs);
?>

<style>
  .option:first-child:hover {
    border-color: <?php echo \esc_attr($config['color']); ?>;
  }
</style>

<div class="wrapper">
  <div class="header">
    <img class="logo" src="<?php echo \esc_attr(\plugins_url('assets/logos/' . $config['logo'], __FILE__)); ?>" alt="Logo"/>
  </div>
  <div class="onboardingpage">
    <h1 class="headline"><?php \esc_html_e('How do you want to create your new website?', 'ionos-assistant'); ?></h1>
    <div class="container">
      <div class="options">
        <div class="option" style=":hover{border-color: <?php echo \esc_attr($config['color']); ?>;}">
          <a href="<?php echo \esc_attr(\admin_url('admin.php?page=extendify-launch')); ?>" class="link-btn">
            <div class="option-content">
              <span class="info-text" style="background-color: <?php echo \esc_attr($config['color']); ?>;"><?php \esc_html_e('Online in a few minutes', 'ionos-assistant'); ?></span>
              <img src="<?php echo \esc_url(
                \plugins_url('assets/artificial-intelligence.png', __FILE__)
              ); ?>" alt="Artificial Intelligence Illustration"/>
              <h3><?php \esc_html_e('With AI', 'ionos-assistant'); ?></h3>
              <p><?php \esc_html_e(
                'Automatic website creation based on your preferences and requirements. Including ready-to-go copy and images.',
                'ionos-assistant'
              ); ?></p>
              <p style="font-weight:700"><?php \esc_html_e('100% WordPress', 'ionos-assistant'); ?></p>
            </div>
          </a>
        </div>
        <div class="option">
          <a href="<?php echo \esc_attr(
            \admin_url('admin.php?page=' . strtolower(\get_option('ionos_group_brand_menu', 'ionos')))
          ); ?>" class="link-btn">
            <div class="option-content">
              <img src="<?php echo \esc_url(\plugins_url('assets/user-interface.png', __FILE__)); ?>" alt="User Interface Illustration"/>
              <h3><?php \esc_html_e('Do it yourself', 'ionos-assistant'); ?></h3>
              <p><?php \esc_html_e(
                'You decide how to create your website. No worries, you can also start the AI creation from the WordPress Dashboard later.',
                'ionos-assistant'
              ); ?></p>
            </div>
          </a>
        </div>
      </div>

      <div class="checkbox-option">
        <span>
          <?php
          printf(
            // translators: %1$s, %2$s, %3$s and %4$s are placeholders for html tags.
            \esc_html__(
              'By using AI features you agree to the OpenAI %1$sTerms of Use%2$s and %3$sPrivacy Policy%4$s',
              'ionos-assistant'
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
