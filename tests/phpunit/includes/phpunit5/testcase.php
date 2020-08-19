<?php
/**
 * This file is for PHPUnit 5-7, it uses PHP 5.6+ syntax for the PHPUnit functions.
 */

require_once dirname( __DIR__ ) . '/abstract-testcase.php';
require_once dirname( __DIR__ ) . '/phpunit-compat-traits/trait-wp-php5-test-framework.php';
require_once dirname( __DIR__ ) . '/phpunit-compat-traits/trait-wp-phpunit9-compat.php';
require_once dirname( __DIR__ ) . '/phpunit-compat-traits/trait-wp-phpunit8-compat.php';
require_once dirname( __DIR__ ) . '/phpunit-compat-traits/trait-wp-phpunit7-compat.php';

/**
 * Defines a basic fixture to run multiple tests.
 *
 * Resets the state of the WordPress installation before and after every test.
 *
 * Includes utility functions and assertions useful for testing WordPress.
 *
 * All WordPress unit tests should inherit from this class.
 */
class WP_UnitTestCase extends WP_UnitTestCase_Base {
	use WP_PHP5_Test_Framework;

	use WP_PHPUnit9_Compat;
	use WP_PHPUnit8_Compat;
	use WP_PHPUnit7_Compat;
}
