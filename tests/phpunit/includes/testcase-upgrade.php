<?php

abstract class WP_Upgrader_UnitTestCase extends WP_UnitTestCase {

	// This might not actually install the plugin, as it may already exist.
	// We're not interested in actually installing the plugin though, just the steps prior to the install.
	function install_plugin_and_return_messages( $plugin_slug ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); // For plugins_api()

		if ( preg_match( '!^https?://!i', $plugin_slug ) ) {
			// The $plugin_slug looked like a URL, so we'll install that.
			$package_url = $plugin_slug;
		} else {
			$api = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug ) );
			// Temporary error
			if ( is_wp_error( $api ) ) {
				wp_die( $api );
			}
			$package_url = $api->download_link;
		}

		$skin     = new WP_Tests_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$upgrader->install( $package_url );

		return $skin->get_upgrade_messages();
	}

	// TODO Filter - Ensure that all requests to WordPress.org include the nostats parameter.

	// Filter - Ensure WordPress has a single invalid key.
	function filter_wp_trusted_keys_only_invalid_key() {
		return array(
			$this->helper_random_public_key(),
		);
	}
	// Filter - Prefix a random key to WordPress's existing keys.
	function filter_wp_trusted_keys_prefix_invalid_key( $keys ) {
		return array_merge(
			array( $this->helper_random_public_key() ),
			$keys
		);
	}
	// Helper - Generate a new random public key.
	function helper_random_public_key() {
		static $key = false;
		if ( $key ) {
			return $key;
		}

		// Generate a new key.
		$random_keypair    = sodium_crypto_sign_keypair();
		$random_public_key = sodium_crypto_sign_publickey( $random_keypair );
		return base64_encode( $random_public_key );
	}
}
