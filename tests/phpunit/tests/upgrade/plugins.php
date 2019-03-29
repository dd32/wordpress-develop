<?php

class Plugin_Upgrader_Tests extends WP_Upgrader_UnitTestCase {
	/**
	 * Integration test - Install a plugin, make sure it succeeds.
	 * @group upgrade-tests
	 */
	function test_install_plugin_hello_dolly() {
		$messages = $this->install_plugin_and_return_messages( 'hello-dolly' );
		$this->assertContains( 'Plugin installed successfully.', $messages );

		/*
		foreach ( $messages as $message ) {
			// TODO: This is a bit fragile and has to be kept in sync with verify_file_signature(), and needs to be enabled for all plugins first.
			$this->assertNotContains( 'could not be verified', $message );
		}
		*/

		// Now install it again, it should fail.
		$messages = $this->install_plugin_and_return_messages( 'hello-dolly' );
		$this->assertNotContains( 'Plugin installed successfully.', $messages );
	}

	/**
	 * Integration test - Install a plugin, make sure it succeeds.
	 * @group upgrade-tests
	 */
	function test_install_plugin_hello_dolly_signing_failure() {

		add_filter( 'wp_trusted_keys', array( $this, 'filter_wp_trusted_keys_only_invalid_key' ) );

		// We don't care if this installation succeeds, we just need to get to the 'downloading' phase which is before it bails because it's already installed.
		$messages = $this->install_plugin_and_return_messages( 'hello-dolly' );

		remove_filter( 'wp_trusted_keys', array( $this, 'filter_wp_trusted_keys_only_invalid_key' ) );

		$signing_failed = false;
		foreach ( $messages as $message ) {
			$signing_failed |= ( false !== stripos( 'could not be verified', $message ) );
		}

		$this->assertTrue( $signing_failed );
	}

	/**
	 * Integration test - Install a plugin, make sure it succeeds.
	 * @group upgrade-tests
	 */
	function test_install_plugin_hello_dolly_add_bad_key() {
		$this->markTestSkipped( 'Plugins do not yet have signatures.' );

		add_filter( 'wp_trusted_keys', array( $this, 'filter_wp_trusted_keys_prefix_invalid_key' ) );

		// We don't care if this installation succeeds, we just want to make sure that
		$messages = $this->install_plugin_and_return_messages( 'hello-dolly' );

		remove_filter( 'wp_trusted_keys', array( $this, 'filter_wp_trusted_keys_prefix_invalid_key' ) );

		// There should be NO update strings which mention failures, if this plugin has signatures available.
		foreach ( $messages as $message ) {
			// TODO: This is a bit fragile and has to be kept in sync with verify_file_signature()
			$this->assertNotContains( 'could not be verified', $message );
		}
	}

	/**
	 * Integration test - Install a plugin from Github, make sure it succeeds.
	 * @group upgrade-tests-plugins
	 */
	function test_install_plugin_github_wordpress_importer() {
		$messages = $this->install_plugin_and_return_messages( 'https://github.com/WordPress/wordpress-importer/archive/master.zip' );

		var_dump( $messages );
		$this->assertContains( 'Plugin installed successfully.', $messages );

		foreach ( $messages as $message ) {
			// TODO: This is a bit fragile and has to be kept in sync with verify_file_signature()
			$this->assertNotContains( 'could not be verified', $message );
		}
	}

	/**
	 * Integration test - Install a plugin, check updates pass.
	 * @group upgrade-tests
	 */
	function test_update_plugin_hello_dolly() {
		$hello_dolly = WP_PLUGIN_DIR . '/hello-dolly/hello.php';
		// If the plugins already installed from another test, save some time.
		if ( ! file_exists( $hello_dolly ) ) {
			$messages = $this->install_plugin_and_return_messages( 'hello-dolly' );
			$this->assertContains( 'Plugin installed successfully.', $messages );
		}

		// Lower the version string of the plugin..
		$contents = file_get_contents( $hello_dolly );
		$contents = preg_replace_callback(
			'!^([\s*]*Version:\s*)([\d.]+)$!im',
			function( $m ) {
				return $m[1] . ( floatval( $m[2] ) - 0.1 );
			},
			$contents
		);
		file_put_contents( $hello_dolly, $contents );

		// Get the update..
		wp_update_plugins();

		$skin     = new WP_Tests_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->upgrade(
			plugin_basename( $hello_dolly )
		);

		$messages = $skin->get_upgrade_messages();

		$this->assertTrue( $result );
		$this->assertContains( 'Plugin updated successfully.', $messages );

		/*
		foreach ( $messages as $message ) {
			// TODO: This is a bit fragile and has to be kept in sync with verify_file_signature(), and needs to be enabled for all plugins first.
			$this->assertNotContains( 'could not be verified', $message );
		}
		*/

	}

	function tearDown() {
		delete_plugins(
			[
				'hello-dolly/hello.php',
				'wordpress-importer-master/wordpress-importer.php',
			]
		);
	}
}
