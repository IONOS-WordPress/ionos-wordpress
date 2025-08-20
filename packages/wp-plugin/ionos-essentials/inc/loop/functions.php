<?php

namespace ionos\essentials\loop;

const SURVEYS_URL                       = 'https://s3-de-central.profitbricks.com/web-hosting/loop-surveys/ionos-standard.json';
const SURVEYS_TRANSIENT_NAME            = 'ionos_loop_survey_collection';
const SURVEYS_TRANSIENT_EXPIRATION_TIME = 10800; // 3h
/**
 * Gets the plugin slug from a plugin filename.
 *
 * @param string $plugin_file Path to the plugin File.
 *
 * @return string
 */
function get_plugin_slug( $plugin_file ) {
	if ( false === strpos( $plugin_file, '/' ) ) {
		return basename( $plugin_file, '.php' );
	}
	return dirname( $plugin_file );
}

/**
 * Normalizes version strings to the format x.y, dropping all patch versions and possible other comments.
 * Will return an empty string if it does not match x.y format.
 *
 * @param string $version_string The version string to normalize.
 * @param bool   $long           If true, will return x.y.z format.
 *
 * @return string|null The normalized version string or null if it does not match the format.
 */
function normalize_version_string( $version_string, $long = false ) {
	if ( $long === true ) {
		$pattern = '/^(\d+\.\d+(?:\.\d+)?).*/';
	} else {
		$pattern = '/^(\d+\.\d+).*/';
	}

	$version_string = trim( $version_string );

	if ( ! preg_match( $pattern, $version_string ) ) {
		return null;
	}

	return preg_replace( $pattern, '$1', $version_string );
}

/**
 * Gets the URL of the Rest API.
 *
 * @return string The URL of the REST API.
 */
function get_loop_rest_api() {
	$api      = get_rest_url( null, 'ionos/v1' );
	$find     = 'wp-json';
	$position = strpos( $api, $find );
	if ( $position === false ) {
		return $api;
	}

	return substr( $api, $position + strlen( $find ) );
}


