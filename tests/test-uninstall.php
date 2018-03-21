<?php
/**
 * Class UninstallTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Tests Relevanssi uninstall process.
 *
 * @group   uninstall
 */
class UninstallTest extends WP_UnitTestCase {
	/**
	 * Installs Relevanssi.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
	}

	/**
	 * Tests uninstalling.
	 */
	public function test_uninstall() {
		global $wpdb, $relevanssi_variables;
		// phpcs:disable WordPress.WP.PreparedSQL

		$relevanssi_table     = $relevanssi_variables['relevanssi_table'];
		$relevanssi_log       = $relevanssi_variables['log_table'];
		$relevanssi_stopwords = $relevanssi_variables['stopword_table'];

		$this->factory->post->create_many( 100 );
		relevanssi_build_index( false, false, 200, false );

//		relevanssi_uninstall();
/*
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $relevanssi_table ) );
		$this->assertEquals( '', $table_exists );

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $relevanssi_log ) );
		$this->assertEquals( '', $table_exists );

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $relevanssi_stopwords ) );
		$this->assertEquals( '', $table_exists );

		$options = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s", 'relevanssi%' ) );
		$this->assertEquals( 0, $options );*/
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		relevanssi_uninstall();
	}
}
