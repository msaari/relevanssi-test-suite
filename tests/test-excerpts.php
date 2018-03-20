<?php
/**
 * Class ExcerptTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi excerpts and highlights.
 */
class ExcerptTest extends WP_UnitTestCase {

	/**
	 * Test indexing process.
	 *
	 * Creates new posts. Relevanssi is active and should index them automatically.
	 * Check if there is correct amount of posts in the index. Then rebuild the
	 * index and see if the total still matches.
	 */
	public function test_excerpts() {
		global $wpdb, $relevanssi_variables;
		// phpcs:disable WordPress.WP.PreparedSQL

		$excerpt_length = 30;

		update_option( 'relevanssi_excerpts', 'on' );
		update_option( 'relevanssi_excerpt_length', $excerpt_length );
		update_option( 'relevanssi_excerpt_type', 'words' );
		update_option( 'relevanssi_highlight', 'strong' );

		// Truncate the index.
		relevanssi_truncate_index();

		$post_id = $this->factory->post->create();

		$post_content = <<<END
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Ipsum a arcu cursus vitae congue mauris
rhoncus. Vitae suscipit tellus mauris a diam maecenas sed enim ut. At elementum eu
facilisis sed odio morbi quis commodo. Urna et pharetra pharetra massa massa
ultricies mi quis hendrerit. Sed ullamcorper morbi tincidunt ornare massa eget. At
tellus at urna condimentum mattis pellentesque id. Fermentum et sollicitudin ac orci
phasellus egestas tellus rutrum tellus. Nec tincidunt praesent semper feugiat nibh
sed pulvinar proin gravida. Id cursus metus aliquam eleifend mi. Adipiscing diam
donec adipiscing tristique risus. Vel pretium lectus quam id leo. Id nibh tortor id
aliquet lectus proin nibh nisl condimentum. Interdum posuere lorem ipsum dolor.

Purus viverra accumsan in nisl nisi scelerisque eu ultrices vitae. Nulla aliquet
enim tortor at. Massa vitae tortor condimentum lacinia. Sit amet consectetur
adipiscing elit ut aliquam purus. Amet facilisis magna etiam tempor orci eu lobortis.
Molestie a iaculis at erat pellentesque adipiscing commodo elit at. Proin libero nunc
consequat interdum varius sit. Eget nunc lobortis mattis aliquam faucibus purus in
massa. Vehicula ipsum a arcu cursus vitae congue. Accumsan lacus vel facilisis
volutpat est. Keyword ornare massa eget egestas purus viverra accumsan in nisl.
END;
		$args         = array(
			'ID'           => $post_id,
			'post_content' => $post_content,
		);

		wp_update_post( $args );

		// Search for "keyword" in posts.
		$args = array(
			's'           => 'keyword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$post  = $posts[0];

		$words = count( explode( ' ', $post->post_excerpt ) );
		// The excerpt should be as long as we wanted it to be.
		$this->assertEquals( $excerpt_length, $words );

		$highlight_location = strpos( $post->post_excerpt, '<strong>' );
		// There should be some highlighting.
		$this->assertNotFalse( $highlight_location );

		$excerpt_length = 50;
		update_option( 'relevanssi_excerpt_length', $excerpt_length );
		$new_excerpt = relevanssi_do_excerpt( $post, 'keyword' );

		$words = count( explode( ' ', $new_excerpt ) );
		// The excerpt should still be as long as we wanted it to be.
		$this->assertEquals( $excerpt_length, $words );
	}
}
