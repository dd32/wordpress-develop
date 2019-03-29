<?php

// Note: This file is named 'zz-core.php' in an attempt to run it last within travis runs of this group.
// There's surely a better way, but this solves it for now.

class Core_Upgrader_Tests extends WP_Upgrader_UnitTestCase {
	/**
	 * Integration test - Test a reinstall of WordPress.
	 * @group upgrade-tests
	 */
	function test_core_reinstall_alt() {
		if ( ! getenv( 'WP_TRAVISCI' ) ) {
			$this->markTestSkipped( "We don't appear to be running in a travis environment." );
		}

		// $skin will get first few update messages, and then they'll be available through `update_feedback` instead.
		$skin = new WP_Tests_Upgrader_Skin();
		add_filter( 'update_feedback', [ $skin, 'feedback' ], 0, 1 );

		wp_version_check();
		// Assume that the first upgrade included is either development, or latest.
		$update = get_site_transient( 'update_core' )->updates[0];

		WP_Filesystem( [], ABSPATH, true );
		$upgrader = new Core_Upgrader( $skin );
		$result   = $upgrader->upgrade(
			$update,
			array(
				'allow_relaxed_file_ownership' => true,
			)
		);

		remove_filter( 'update_feedback', [ $skin, 'feedback' ], 1 );

		$messages = $skin->get_upgrade_messages();
		foreach ( $messages as $message ) {
			// TODO: This is a bit fragile and has to be kept in sync with verify_file_signature()
			$this->assertContains( 'could not be verified', $message );
		}

		// $result is the new version on success

		$this->assertNotWPError( $result );
		$this->assertNotFalse( $result );
		$this->assertGreaterThanOrEqual( 1, did_action( '_core_updated_successfully' ) );

	}
}
