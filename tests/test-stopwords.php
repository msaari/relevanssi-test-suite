<?php
/**
 * Class StopwordTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi stopwords.
 */
class StopwordTest extends WP_UnitTestCase {

	/**
	 * Test stopwords.
	 */
	public function test_stopwords() {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		// Truncate the index.
		relevanssi_truncate_index();

		// Set up some Relevanssi settings so we know what to expect.
		$post_count = 10;
		$post_ids   = $this->factory->post->create_many( $post_count );

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) );
		// Every posts should have the word 'content'.
		$this->assertEquals( $post_count, $word_content_count );

		$success = relevanssi_add_stopword( 'content', false );
		// Adding the stopword should be successful.
		$this->assertTrue( $success );

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) );
		// No posts should have the word 'content'.
		$this->assertEquals( 0, $word_content_count );

		$success = relevanssi_remove_stopword( 'content', false );
		// Removing the stopword should work.
		$this->assertTrue( $success );
	}
}
