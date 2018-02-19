<?php
/**
 * Class IndexingTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi indexing.
 */
class IndexingTest extends WP_UnitTestCase {

	/**
	 * Test indexing process.
	 *
	 * Creates new posts. Relevanssi is active and should index them automatically.
	 * Check if there is correct amount of posts in the index. Then rebuild the
	 * index and see if the total still matches.
	 */
	public function test_indexing() {
		global $wpdb, $relevanssi_variables;

		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		$post_count       = 10;
		$post_ids         = $this->factory->post->create_many( $post_count );

		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );

		$this->assertEquals( $post_count, $distinct_docs );

		// This function should be able to count the number of posts.
		$counted_total = relevanssi_count_total_posts();
		$this->assertEquals( $post_count, $counted_total );

		// This function should find 0 missing posts.
		$missing_total = relevanssi_count_missing_posts();
		$this->assertEquals( 0, $missing_total );

		// Truncate the index.
		relevanssi_truncate_index();

		// Now there should be 0 posts in the index.
		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		$this->assertEquals( 0, $distinct_docs );

		// And $post_count posts should be missing.
		$missing_total = relevanssi_count_missing_posts();
		$this->assertEquals( $post_count, $missing_total );

		// Rebuild the index.
		relevanssi_build_index( false, false, 200, false );

		// Are the now $post_count posts in the index?
		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		$this->assertEquals( $post_count, $distinct_docs );

		// Try deleting a post from index.
		$delete_post_id = array_pop( $post_ids );
		relevanssi_remove_doc( $delete_post_id );

		// There should be zero rows for this post.
		$post_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE doc=$delete_post_id" );
		$this->assertEquals( 0, $post_rows );

		// It's necessary to hook comment indexing to 'wp_insert_comment'. It's
		// usually hooked to 'comment_post', but that doesn't trigger from the
		// factory.
		add_action( 'wp_insert_comment', 'relevanssi_comment_index' );

		// Enable comment indexing.
		update_option( 'relevanssi_index_comments', 'normal' );

		// Get a post ID and add some comments to it.
		$comment_post_id = array_pop( $post_ids );
		$comment_ids = $this->factory->comment->create_many( 10, array( 'comment_post_ID' => $comment_post_id ) );

		// There should be one post with comments in the index.
		$comment_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE term='comment'" );
		$this->assertEquals( 1, $comment_rows );

	}
}

/**
 * Helpful little debugging function.
 *
 * @param int $post_id Post ID to examine. Default null, will then dump the whole DB.
 */
function dump_relevanssi_db( $post_id = null ) {
	global $wpdb, $relevanssi_variables;

	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	$post_id_query = '';
	if ( null !== $post_id ) {
		$post_id_query = " WHERE doc=$post_id ";
	}
	var_dump( $wpdb->get_results( "SELECT * FROM $relevanssi_table $post_id_query" ) );
}
