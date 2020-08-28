<?php
/**
 * @group formatting
 * @group post
 */
class Tests_Formatting_SanitizePost extends WP_UnitTestCase {

	/**
	 * @ticket 22324
	 */
	function test_int_fields() {
		$post = self::factory()->post->create_and_get();

		$this->assertIsInt( $post->ID );
		$this->assertIsInt( $post->post_parent );
		$this->assertIsInt( $post->menu_order );
		$this->assertIsString( $post->post_author );
		$this->assertIsString( $post->comment_count );
	}
}
