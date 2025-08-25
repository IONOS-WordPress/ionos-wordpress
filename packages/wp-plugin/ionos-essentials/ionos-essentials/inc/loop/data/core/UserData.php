<?php

namespace ionos\essentials\loop\data\core;

use ionos\essentials\loop\data\DataProvider;

/**
 * Data Provider for User Accounts.
 */
class UserData extends DataProvider {
	/**
	 * Collects how many user accounts exist in which roles.
	 *
	 * @return array
	 */
	protected function collect_data() {
		return count_users( 'memory' );
	}
}
