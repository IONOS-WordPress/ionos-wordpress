<?php

namespace ionos\essentials\login;

?>
  <?php
        $config = get_brand_config();
if (null === $config) {
  return;
}
$header_image_src = $config['logo_default'];
$header_image_alt = $config['name'];
?>

<section class="header">
	<?php if ($header_image_src): ?>
        <img src="<?php echo esc_url($header_image_src); ?>" alt="<?php echo esc_attr($header_image_alt); ?>" class="logo">
	<?php endif; ?>
</section>
