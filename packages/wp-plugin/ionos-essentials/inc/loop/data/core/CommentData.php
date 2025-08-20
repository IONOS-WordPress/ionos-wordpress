<?php

namespace ionos\essentials\loop\data\core;

use ionos\essentials\loop\data\DataProvider;

/**
 * Data provider for posts and any post types.
 */
class CommentData extends DataProvider {

	/**
	 * Collects all post/post type related data.
	 *
	 * @return array
	 */
	protected function collect_data() {
		global $wpdb;

		$comment_status = $wpdb->get_results(
			'SELECT comment_approved, count( comment_ID ) AS count FROM ' . $wpdb->comments . ' GROUP BY comment_approved ',
			ARRAY_A
		);

		$comment_counts = [
			'approved'     => 0,
			'not_approved' => 0,
			'spam'         => 0,
			'trash'        => 0,
			'total'        => 0,
		];

		foreach ( $comment_status as $status ) {
			if ( $status['comment_approved'] === '1' ) {
				$key = 'approved';
			} elseif ( $status['comment_approved'] === '0' ) {
				$key = 'not_approved';
			} else {
				$key = $status['comment_approved'];
			}

			$comment_counts[ $key ]   = (int) $status['count'];
			$comment_counts['total'] += $status['count'];
		}

		return $comment_counts;
	}
}
