<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Relevanssi_Premium
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/relevanssi.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

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
