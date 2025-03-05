<?php

namespace ionos_wordpress\test_plugin\feature_1\blocks\block_2;

?>
<p <?php echo \wp_kses_data(\get_block_wrapper_attributes()); ?>>
	<?php \esc_html_e('Dynamic Block 2 – hello from a dynamic block!', 'block-2'); ?>
</p>
