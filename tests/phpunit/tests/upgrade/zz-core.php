<?php

// Note: This file is named 'zz-core.php' in an attempt to run it last within travis runs of this group.
// There's surely a better way, but this solves it for now.

class Core_Upgrader_Tests extends WP_Upgrader_UnitTestCase {
	/**
	 * Integration test - Test a reinstall of WordPress.
	 * @group upgrade-tests
	 */
	function test_core_reinstall() {
		if ( ! getenv( 'WP_TRAVISCI' ) ) {
			$this->markTestSkipped( "We don't appear to be running in a travis environment." );
		}

		// $messages contains the during-upgrade texts, $output below will contain the success/fail messages.
		$messages = [];
		$message_recorder = function( $message ) use ( $messages ) {
			$messages[] = $message;
			return $message;
		};
		add_filter( 'update_feedback', $message_recorder, 1, 1 );

		wp_version_check();

		ob_start();
		// Ew. This can only be done once..
		include ABSPATH . 'wp-admin/update-core.php';
		do_core_upgrade( true );
		$output = ob_end_clean();

		remove_filter( 'update_feedback', $message_recorder, 1 );

		var_dump( $output, $messages );

		$this->assertContains( 'WordPress updated successfully', $output );
	}

	/**
	 * Integration test - Test a reinstall of WordPress.
	 * @group upgrade-tests
	 */
	function test_core_reinstall_alt() {
		if ( ! getenv( 'WP_TRAVISCI' ) ) {
			$this->markTestSkipped( "We don't appear to be running in a travis environment." );
		}

		// $messages contains the during-upgrade texts, $output below will contain the success/fail messages.
		$messages = [];
		$message_recorder = function( $message ) use ( $messages ) {
			$messages[] = $message;
			return $message;
		};
		add_filter( 'update_feedback', $message_recorder, 1, 1 );

		wp_version_check();

		$update  = find_core_update( false, 'en_US' );
		WP_Filesystem( [], ABSPATH, true );
		$update->response = 'reinstall';
		$upgrader = new Core_Upgrader();
		$result   = $upgrader->upgrade(
			$update,
			array(
				'allow_relaxed_file_ownership' => true,
			)
		);

		remove_filter( 'update_feedback', $message_recorder, 1 );

		var_dump( $result, $messages );

		$this->assertTrue( $result );

	}
}
