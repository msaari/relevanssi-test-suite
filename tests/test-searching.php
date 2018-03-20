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
		$relevanssi_log   = $relevanssi_variables['log_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		// Truncate the index.
		relevanssi_truncate_index();

		// Set up some Relevanssi settings so we know what to expect.
		update_option( 'relevanssi_index_fields', 'visible' );
		update_option( 'relevanssi_index_users', 'on' );
		update_option( 'relevanssi_index_subscribers', 'on' );
		update_option( 'relevanssi_index_author', 'on' );
		update_option( 'relevanssi_implicit_operator', 'AND' );
		update_option( 'relevanssi_fuzzy', 'sometimes' );
		update_option( 'relevanssi_log_queries', 'on' );

		$post_count = 10;
		$post_ids   = $this->factory->post->create_many( $post_count );

		$user_count = 10;
		$user_ids   = $this->factory->user->create_many( $user_count );

		$post_author_id = $user_ids[0];

		$counter     = 0;
		$invisible   = 0;
		$visible     = 0;
		$and_matches = 0;
		$titles      = array();
		foreach ( $post_ids as $id ) {
			// Make five posts have the word 'buzzword' in a visible custom field and
			// rest of the posts have it in an invisible custom field.
			if ( $counter < 5 ) {
				update_post_meta( $id, '_invisible', 'buzzword' );
				$invisible++;
				update_post_meta( $id, 'keywords', 'cat dog' );
				$and_matches++;
			} else {
				update_post_meta( $id, 'visible', 'buzzword' );
				$visible++;
				update_post_meta( $id, 'keywords', 'cat' );
			}

			$title    = substr( md5( rand() ), 0, 7 );
			$titles[] = $title;
			// Set the post author and title.
			$args = array(
				'ID'          => $id,
				'post_author' => $post_author_id,
				'post_title'  => $title,
			);
			wp_update_post( $args );

			$counter++;
		}

		sort( $titles );

		// Name the post author 'displayname user'.
		$args = array(
			'ID'           => $post_author_id,
			'display_name' => 'displayname user',
		);
		wp_update_user( $args );

		// Rebuild the index.
		relevanssi_build_index( false, false, 200, false );

		// Search for "content" in posts.
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

		// Check that log is stored correctly.
		$hits = $wpdb->get_var( $wpdb->prepare( "SELECT hits FROM $relevanssi_log WHERE query = %s", 'content' ) );

		// The log should show $post_count hits.
		$this->assertEquals( $post_count, $hits );

		// Search for "conte" in posts. With the fuzzy search set to "sometimes",
		// this should find all posts.
		$args = array(
			's'           => 'conte',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all the posts.
		$this->assertEquals( $post_count, $query->found_posts );

		update_option( 'relevanssi_fuzzy', 'never' );
		// Search for "conte" in posts. With the fuzzy search set to "never",
		// this should find nothing.
		$args = array(
			's'           => 'conte',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find nothing.
		$this->assertEquals( 0, $query->found_posts );

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

		// Search for "user" to find users.
		$args = array(
			's'           => 'user',
			'post_type'   => 'user',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should match the number of users.
		$this->assertEquals( $user_count, $query->found_posts );

		// Search for "displayname" to find authors.
		$args = array(
			's'           => 'displayname',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all posts.
		$this->assertEquals( $post_count, $query->found_posts );

		// Search for "cat dog" with AND enabled.
		$args = array(
			's'           => 'cat dog',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find the posts with both words.
		$this->assertEquals( $and_matches, $query->found_posts );

		// Search for "cat dog" with the OR operator.
		$args = array(
			's'           => 'cat dog',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
			'operator'    => 'OR',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all posts.
		$this->assertEquals( $post_count, $query->found_posts );

		// Search for "content" and get some alphabetical ordering. Check the
		// two-level sorting at the same time; all posts should be equally good for
		// relevance for "content", so it should fall back to the alphabetical.
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => 1,
			'post_status' => 'publish',
			'orderby'     => array(
				'relevance'  => 'desc',
				'post_title' => 'asc',
			),
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		$first_post       = $posts[0];
		$first_post_title = $titles[0];

		// First post title should match the first title in alpha order.
		$this->assertEquals( $first_post_title, $first_post->post_title );

	}
}
