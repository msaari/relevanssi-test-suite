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
	 * Number of posts generated.
	 *
	 * @var int self::$post_count
	 */
	public static $post_count;

	/**
	 * Number of users generated.
	 *
	 * @var int $this->user_count
	 */
	public static $user_count;

	/**
	 * Number of posts with visible custom fields.
	 *
	 * @var int $visible
	 */
	public static $visible;

	/**
	 * Number of posts that should get an AND match.
	 *
	 * @var int $and_matches
	 */
	public static $and_matches;

	/**
	 * Sets up the index.
	 */
	public static function setUpBeforeClass() {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
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

		self::$post_count = 10;
		$post_ids         = self::factory()->post->create_many( self::$post_count );

		self::$user_count = 10;
		$user_ids         = self::factory()->user->create_many( self::$user_count );

		$post_author_id = $user_ids[0];

		$counter           = 0;
		self::$visible     = 0;
		self::$and_matches = 0;
		foreach ( $post_ids as $id ) {
			// Make five posts have the word 'buzzword' in a visible custom field and
			// rest of the posts have it in an invisible custom field.
			if ( $counter < 5 ) {
				update_post_meta( $id, '_invisible', 'buzzword' );
				update_post_meta( $id, 'keywords', 'cat dog' );
				self::$and_matches++;
			} else {
				update_post_meta( $id, 'visible', 'buzzword' );
				self::$visible++;
				update_post_meta( $id, 'keywords', 'cat' );
			}

			$title = substr( md5( rand() ), 0, 7 );

			// Set the post author and title.
			$args = array(
				'ID'          => $id,
				'post_author' => $post_author_id,
				'post_title'  => $title,
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
	}

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
		$this->assertEquals( self::$post_count, $query->found_posts );
		$this->assertEquals( self::$post_count, count( $posts ) );

		// Check that log is stored correctly.
		$hits = $wpdb->get_var( $wpdb->prepare( "SELECT hits FROM $relevanssi_log WHERE query = %s", 'content' ) );

		// The log should show self::$post_count hits.
		$this->assertEquals( self::$post_count, $hits );
	}

	/**
	 * Tests partial matching.
	 *
	 * Tries fuzzy searching, then disables that.
	 */
	public function test_partial_matching() {
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
		$this->assertEquals( self::$post_count, $query->found_posts );

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
	}

	/**
	 * Tests custom field searching.
	 *
	 * Should find content that is in a visible custom field.
	 */
	public function test_custom_fields() {
		// Search for "buzzword" to match custom fields.
		$args = array(
			's'           => 'buzzword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should match the posts with a visible custom field.
		$this->assertEquals( self::$visible, $query->found_posts );
	}

	/**
	 * Tests user search.
	 *
	 * Should find user profiles.
	 */
	public function test_user_search() {
		// Search for "user" to find users.
		$args = array(
			's'           => 'user',
			'post_type'   => 'user',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should match the number of users.
		$this->assertEquals( self::$user_count, $query->found_posts );
	}

	/**
	 * Tests author search.
	 *
	 * Should find posts by the author name.
	 */
	public function test_author_search() {
		// Search for "displayname" to find authors.
		$args = array(
			's'           => 'displayname',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all posts.
		$this->assertEquals( self::$post_count, $query->found_posts );
	}

	/**
	 * Tests AND and OR search.
	 *
	 * The operator default is AND. Test that, then switch to OR and see if
	 * the results still make sense.
	 */
	public function test_operators() {
		// Search for "cat dog" with AND enabled.
		$args = array(
			's'           => 'cat dog',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find the posts with both words.
		$this->assertEquals( self::$and_matches, $query->found_posts );

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
		$this->assertEquals( self::$post_count, $query->found_posts );
	}

	/**
	 * Tests sorting.
	 *
	 * Fetches posts with two layers of sorting: first by relevance, then by post
	 * title. All posts are equally relevant, so the order should be by title.
	 */
	public function test_sorting() {
		// Search for "content" and get some alphabetical ordering. Check the
		// two-level sorting at the same time; all posts should be equally good for
		// relevance for "content", so it should fall back to the alphabetical.
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
			'orderby'     => array(
				'relevance'  => 'desc',
				'post_title' => 'asc',
			),
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// Get the titles of the posts found, sort them in alphabetical order.
		$titles = array();
		foreach ( $posts as $post ) {
			$titles[] = $post->post_title;
		}
		sort( $titles );

		$first_post       = $posts[0];
		$first_post_title = $titles[0];

		// First post title should match the first title in alpha order.
		$this->assertEquals( $first_post_title, $first_post->post_title );
	}
}
