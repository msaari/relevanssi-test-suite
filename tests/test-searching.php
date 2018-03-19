<?php
/**
 * Class SearchingTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi searching.
 */
class SearchingTest extends WP_UnitTestCase {

	/**
	 * Test searching process.
	 *
	 * Creates some posts, tries to find them.
	 */
	public function test_searching() {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		// Truncate the index.
		relevanssi_truncate_index();

		// Set up some Relevanssi settings so we can test advanced searching
		// features.
		update_option( 'relevanssi_index_fields', 'visible' );
		update_option( 'relevanssi_index_users', 'on' );
		update_option( 'relevanssi_index_subscribers', 'on' );
		update_option( 'relevanssi_index_author', 'on' );

		$post_count = 10;
		$post_ids   = $this->factory->post->create_many( $post_count );

		$user_count = 10;
		$user_ids   = $this->factory->user->create_many( $user_count );

		$post_author_id = $user_ids[0];

		$counter   = 0;
		$invisible = 0;
		$visible   = 0;
		foreach ( $post_ids as $id ) {
			// Make five posts have the word 'buzzword' in a visible custom field and
			// rest of the posts have it in an invisible custom field.
			if ( $counter < 5 ) {
				update_post_meta( $id, '_invisible', 'buzzword' );
				$invisible++;
			} else {
				update_post_meta( $id, 'visible', 'buzzword' );
				$visible++;
			}

			// Set the post author.
			$args = array(
				'ID'          => $id,
				'post_author' => $post_author_id,
			);
			wp_update_post( $args );

			$counter++;
		}

		// Name the post author 'displayname user'.
		$args = array(
			'ID'           => $post_author_id,
			'display_name' => 'displayname user',
		);
		wp_update_user( $args );

		// Rebuild the index.
		relevanssi_build_index( false, false, 200, false );

		// Search for "content" in  posts.
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// These should both match the number of posts in the index.
		$this->assertEquals( $post_count, $query->found_posts );
		$this->assertEquals( $post_count, count( $posts ) );

		// Search for "buzzword" to match custom fields.
		$args = array(
			's'           => 'buzzword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should match the posts with a visible custom field.
		$this->assertEquals( $visible, $query->found_posts );

		// Search for "user" to match custom fields.
		$args = array(
			's'           => 'user',
			'post_type'   => 'user',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should match the posts with a visible custom field.
		$this->assertEquals( $user_count, $query->found_posts );

		// Search for "nicename" to match custom fields.
		$args = array(
			's'           => 'displayname',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should match the posts with a visible custom field.
		$this->assertEquals( $post_count, $query->found_posts );
	}
}
