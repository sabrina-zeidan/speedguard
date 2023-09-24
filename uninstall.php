<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://sabrinazeidan.com/
 * @since      1.0.0
 *
 * @package    Speedguard
 */
// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
function speedguard_delete_data() {
	// Delete CPTs
	$guarded_pages = get_posts( [
		'post_type'     => 'guarded-page',
		'post_status'   => 'any',
		'fields'        => 'ids',
		'no_found_rows' => true,
	] );
	foreach ( $guarded_pages as $guarded_page_id ) {
		SpeedGuard_Tests::delete_test_fn( $guarded_page_id );
	}
	// Delete posts meta
	$guarded_posts = get_posts( [
		'post_type'     => 'any',
		'post_status'   => 'any',
		'fields'        => 'ids',
		'meta_query'    => [
			'relation' => 'AND',
			[
				'key'     => 'speedguard_on',
				'compare' => 'EXISTS',
			],
		],
		'no_found_rows' => true,
	] );
	foreach ( $guarded_posts as $guarded_post_id ) {
		delete_post_meta( $guarded_post_id, 'speedguard_on' );
	}
	// Delete terms meta
	$the_terms = get_terms( [
		'fields'     => 'ids',
		'hide_empty' => false,
		'meta_query' => [
			[
				'key'     => 'speedguard_on',
				'compare' => 'EXISTS',
			],
		],
	] );
	foreach ( $the_terms as $term_id ) {
		delete_term_meta( $term_id, 'speedguard_on' );
	}
	// Delete options
	$speedguard_options = [
		'speedguard_options',
		'sg_origin_results'
	];
	foreach ( $speedguard_options as $option_name ) {
		delete_option( $option_name );
		if ( is_multisite() ) {
			delete_site_option( $option_name );
		}
	}
	// Delete transients
	$speedguard_transients = [
		'speedguard-notice-activation',
		'speedguard-notice-deactivation',
		'speedguard_tests_in_queue',
		'speedguard_test_in_progress',
		'speedguard_sending_request_now',
		'speedguard_last_test_is_done'
	];
	foreach ( $speedguard_transients as $speedguard_transient ) {
		delete_transient( $speedguard_transient );
	}
	// Delete CRON jobs
	wp_clear_scheduled_hook( 'speedguard_update_results' );
	wp_clear_scheduled_hook( 'speedguard_email_test_results' );
}

// search all blogs if Multisite
if ( is_multisite() ) {
	$sites = get_sites();
	foreach ( $sites as $site ) {
		$blog_id = $site->blog_id;
		switch_to_blog( $blog_id );
		speedguard_delete_data();
		restore_current_blog();
	}
} else {
	speedguard_delete_data();
}
