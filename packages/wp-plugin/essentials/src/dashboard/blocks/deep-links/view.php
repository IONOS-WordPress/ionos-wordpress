<?php

echo '<ul class="wp-block-list">';
foreach( $links as $url => $anchor ) {
	printf('<li><a href="%s" target="_blank">%s</a></li>', $url, $anchor );
}

echo '</ul>';
