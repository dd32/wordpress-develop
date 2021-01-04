<?php

/**
 * @covers Plugin_Upgrader::upgrade
 *
 * @group  updater
 * @group  plugin_updater
 */
class Tests_Admin_Includes_PluginUpdater_Upgrade extends WP_UnitTestCase {
	private $plugin = array();

	public static function setUpBeforeClass() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	public function setUp() {
		parent::setUp();

		// Remove upgrade hooks which are not required for plugin installation tests
		// and may interfere with the results due to a timeout in external HTTP requests.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );
		remove_action( 'upgrader_process_complete', 'wp_version_check' );
		remove_action( 'upgrader_process_complete', 'wp_update_plugins' );
		remove_action( 'upgrader_process_complete', 'wp_update_themes' );
	}

	public function tearDown() {
		// Remove the installed plugin.
		if ( $this->plugin['dest_file'] && file_exists( $this->plugin['dest_file'] ) ) {
			$this->rmdir( $this->plugin['dest_dir'] );
			rmdir( $this->plugin['dest_dir'] );
		}

		delete_site_transient( 'update_plugins' );

		parent::tearDown();
	}

	/**
	 * @dataProvider data_should_not_send_error_report
	 *
	 * @group        51928
	 *
	 * @param array $plugin           Array of plugin information.
	 * @param array $update_plugins   Value for the "update_plugins" transient.
	 * @param array $expected_message Array of expected admin output messages.
	 */
	public function test_should_not_send_error_report( $plugin, $update_plugins, $expected_message ) {
		$this->plugin = $plugin;

		set_site_transient( 'update_plugins', $update_plugins );

		$plugin_upgrader = $this
			->getMockBuilder( Plugin_Upgrader::class )
			->setConstructorArgs( array( $this->mock_skin() ) )
			->setMethods( array( 'send_error_data' ) )
			->getMock();

		$plugin_upgrader
			->expects( $this->never() )
			->method( 'send_error_data' );

		ob_start();
		$plugin_upgrader->upgrade( $plugin['plugin'] );
		$actual_message = ob_get_clean();

		foreach ( $expected_message as $expected ) {
			$this->assertContains( $expected, $actual_message );
		}
	}

	public function data_should_not_send_error_report() {
		return array(
			'when local zip file exists'  => array(
				'plugin'           => array(
					'dest_file' => WP_PLUGIN_DIR . '/hello/hello.php',
					'dest_dir'  => WP_PLUGIN_DIR . '/hello',
					'plugin'    => 'hello-dolly/hello.php',
				),
				'update_plugins'   => (object) array(
					'last_checked' => time(),
					'checked'      => array(
						'hello.php' => '1.6',
					),
					'response'     => array(
						'hello-dolly/hello.php' => (object) array(
							'id'          => 'w.org/plugins/hello-dolly',
							'slug'        => 'hello-dolly',
							'plugin'      => 'hello.php',
							'new_version' => '1.7.2',
							'url'         => 'https://wordpress.org/plugins/hello-dolly/',
							'package'     => DIR_TESTDATA . '/plugins/hello-1.7.2/hello.zip',
						),
					),
				),
				'expected_message' => array(
					'data/plugins/hello-1.7.2/hello.zip',
					<<<MESSAGE
<p>Unpacking the update&#8230;</p>
<p>Installing the latest version&#8230;</p>
<p>Removing the old version of the plugin&#8230;</p>
<p>Plugin updated successfully.</p>
MESSAGE
				,
				),
			),
			'when downloading from w.org' => array(
				'plugin'           => array(
					'dest_file' => WP_PLUGIN_DIR . '/hello-dolly/hello.php',
					'dest_dir'  => WP_PLUGIN_DIR . '/hello-dolly',
					'plugin'    => 'hello-dolly/hello.php',
				),
				'update_plugins'   => (object) array(
					'last_checked' => time(),
					'checked'      => array(
						'hello.php' => '1.6',
					),
					'response'     => array(
						'hello-dolly/hello.php' => (object) array(
							'id'          => 'w.org/plugins/hello-dolly',
							'slug'        => 'hello-dolly',
							'plugin'      => 'hello.php',
							'new_version' => '1.7.2',
							'url'         => 'https://wordpress.org/plugins/hello-dolly/',
							'package'     => 'https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip',
						),
					),
				),
				'expected_message' => array(
					<<<MESSAGE
<div class="wrap"><h1></h1><p>Downloading update from <span class="code">%s</span>&#8230;</p>
<p>The authenticity of <span class="code">hello-dolly.1.7.2.zip</span> could not be verified as no signature was found.</p>
MESSAGE
				,
					<<<MESSAGE
<p>Unpacking the update&#8230;</p>
<p>Installing the latest version&#8230;</p>
<p>Removing the old version of the plugin&#8230;</p>
<p>Plugin updated successfully.</p>
MESSAGE
				,
				),
			),
		);
	}

	/**
	 * @dataProvider data_should_send_error_report
	 *
	 * @group        51928
	 *
	 * @param array $plugin           Array of plugin information.
	 * @param array $update_plugins   Value for the "update_plugins" transient.
	 * @param array $expected_message Array of expected admin output messages.
	 * @param array $expected_stats   Array of expected stats.
	 */
	public function test_should_send_error_report( $plugin, $update_plugins, $expected_message, $expected_stats ) {
		$this->plugin = $plugin;

		set_site_transient( 'update_plugins', $update_plugins );

		$this->setup_test();

		$actual_stats = array();
		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args ) use ( &$actual_stats ) {
				if ( ! isset( $parsed_args['body']['update_stats'] ) ) {
					return $preempt;
				}

				$actual_stats[] = (array) json_decode( $parsed_args['body']['update_stats'] );

				return true;
			},
			10,
			2
		);

		$plugin_upgrader = new Plugin_Upgrader( $this->mock_skin() );

		ob_start();
		$plugin_upgrader->upgrade( $plugin['plugin'] );
		$actual_message = ob_get_clean();

		foreach ( $actual_stats as $index => $stats ) {
			$this->assertContains( $expected_stats[ $index ], $stats );
			$this->assertGreaterThan( 0.0, $stats['time_taken'] );
		}

		foreach ( $expected_message as $expected ) {
			$this->assertContains( $expected, $actual_message );
		}
	}

	public function data_should_send_error_report() {
		$not_available_stats = array(
			array(
				'process'          => 'download_package',
				'update_type'      => 'automatic_plugin_update',
				'name'             => null,
				'update_version'   => null,
				'success'          => false,
				'fs_method'        => 'direct',
				'fs_method_forced' => true,
				'fs_method_direct' => '',
				'error_code'       => 'no_package',
				'error_message'    => 'Installation package not available.',
				'error_data'       => null,
			),
			array(
				'process'          => 'plugin_install',
				'update_type'      => 'automatic_plugin_update',
				'name'             => null,
				'update_version'   => null,
				'success'          => false,
				'fs_method'        => 'direct',
				'fs_method_forced' => true,
				'fs_method_direct' => '',
				'error_code'       => 'no_package',
				'error_message'    => 'Installation package not available.',
				'error_data'       => null,
			),
		);

		return array(
			'when new version does not exist' => array(
				'plugin'           => array(
					'dest_file' => WP_PLUGIN_DIR . '/hello/hello.php',
					'dest_dir'  => WP_PLUGIN_DIR . '/hello',
					'plugin'    => 'hello-dolly/hello.php',
				),
				'update_plugins'   => (object) array(
					'last_checked' => time(),
					'checked'      => array(
						'hello.php' => '1.6',
					),
					'response'     => array(
						'hello-dolly/hello.php' => (object) array(
							'id'          => 'w.org/plugins/hello-dolly',
							'slug'        => 'hello-dolly',
							'plugin'      => 'hello.php',
							'new_version' => '99999',
							'url'         => 'https://wordpress.org/plugins/hello-dolly/',
							'package'     => DIR_TESTDATA . '/plugins/hello-99999/hello.zip',
						),
					),
				),
				'expected_message' => array(
					'data/plugins/hello-99999/hello.zip',
					<<<ERROR_MESSAGE
<p>Unpacking the update&#8230;</p>
<p>The package could not be installed. PCLZIP_ERR_MISSING_FILE (-4) : Missing archive file
ERROR_MESSAGE
				,
				),
				'expected_stats'   => $not_available_stats,
			),
		);
	}

	/**
	 * Mocks WP_Upgrader_Skin::feedback method.
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	private function mock_skin() {
		$skin = $this
			->getMockBuilder( WP_Upgrader_Skin::class )
			->setMethods( array( 'feedback' ) )
			->getMock();

		// Mocks the feedback method to prevent `show_message()` from running, i.e.
		// to avoid it from flushing and ending all output buffers. Why?
		// Avoids printing in the console and allows testing the feedback messages.
		$skin
			->expects( $this->atLeastOnce() )
			->method( 'feedback' )
			->willReturnCallback(
				function ( $message ) use ( $skin ) {
					if ( isset( $skin->upgrader->strings[ $message ] ) ) {
						$message = $skin->upgrader->strings[ $message ];
					}

					echo "<p>$message</p>\n";
				}
			);

		return $skin;
	}

	private function setup_test() {
		// Short-circuit calling w.org for the download.
		add_filter(
			'upgrader_pre_download',
			function ( $reply, $package, $upgrader ) {
				if ( ! empty( $package ) && $upgrader instanceof Plugin_Upgrader ) {
					return $package;
				}

				return $reply;
			},
			10,
			3
		);
	}
}
