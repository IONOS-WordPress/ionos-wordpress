<?php

namespace ionos\essentials\jetpack_flow;

class LoginRedirect {

	public static function register_regular_login_redirect() {
		add_filter( 'login_redirect', [ __CLASS__, 'redirect_after_regular_login' ], 90, 3 );
	}

	public static function register_otl_redirect() {
		add_action( 'one_time_login_after_auth_cookie_set', [ __CLASS__, 'redirect_after_otl' ], 10, 1 );
	}

	public static function register_redirect() {
		self::register_regular_login_redirect();
		self::register_otl_redirect();
	}

	public static function redirect_after_regular_login( $redirect_to, $requested_redirect_to, $user ) {
		return self::redirect_after_login( $user, $redirect_to, $requested_redirect_to );
	}

	public static function redirect_after_otl( $user ) {
		wp_safe_redirect( self::redirect_after_login( $user, admin_url() ) );
		exit;
	}

	protected static function redirect_after_login( $user, $redirect_to, $requested_redirect_to = '' ) {
		return apply_filters( 'ionos_login_redirect_to', $redirect_to, $requested_redirect_to, $user );
	}
}
