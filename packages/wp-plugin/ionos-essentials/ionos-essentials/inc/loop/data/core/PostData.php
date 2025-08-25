<?php

namespace ionos\essentials\loop\data\core;

use ionos\essentials\loop\data\DataProvider;

/**
 * Data provider for posts and any post types.
 */
class PostData extends DataProvider {

	/**
	 * Collects all post/post type related data.
	 *
	 * @return array
	 */
	protected function collect_data() {
		global $wpdb;
		$post_types = get_post_types( [], 'objects' );

		$parsed_post_types = [];

		foreach ( $post_types as $post_type ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT count(ID) as count FROM ' . $wpdb->posts . ' WHERE post_type = %s AND post_status != %s AND post_status != %s LIMIT 1',
					$post_type->name,
					'trash',
					'revision'
				)
			);

			$parsed_post_types[] = [
				'name'  => $post_type->name,
				'count' => (int) $count,
			];
		}

		return $parsed_post_types;
	}
}
