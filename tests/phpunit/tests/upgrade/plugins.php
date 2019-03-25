<?php

class Plugin_Upgrader_Tests extends WP_Upgrader_UnitTestCase {
	/**
	 * Integration test - Install a plugin, make sure it succeeds.
	 * @group upgrade-tests
	 */
	function test_install_plugin_hello_dolly() {
		$messages = $this->install_plugin_and_return_messages( 'hello-dolly' );

		$this->assertContains( 'Plugin installed successfully.', $messages );

		// Now install it again, it should fail.
		$messages = $this->install_plugin_and_return_messages( 'hello-dolly' );

		$this->assertNotContains( 'Plugin installed successfully.', $messages );
	}
}