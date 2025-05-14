<?php

namespace ionos\essentials\jetpack_flow;

class HiddenAdminPage {
	private $page_title;
	private $page_slug;
	private $capability;
	private $callback;

	public function __construct( $page_title, $page_slug, $callback, $capability = 'manage_options' ) {
		$this->page_title = $page_title;
		$this->page_slug = $page_slug;
		$this->capability = $capability;
		$this->callback = $callback;
	}

	public function register_page() {
		add_action( 'admin_menu', function() {
			add_menu_page(
				$this->page_title,
				$this->page_title,
				$this->capability,
				$this->page_slug,
				function() {
					if ( ! $this->is_hidden_page() ) {
						return;
					}

					call_user_func( $this->callback );
				}
			);
			remove_menu_page( $this->page_slug );
		} );
	}

	public function is_hidden_page() {
		global $current_screen;
		$base = isset( $current_screen->base ) ? $current_screen->base : '';
		if ( "toplevel_page_$this->page_slug" !== $base ) {
			return false;
		}

		return true;
	}
}
