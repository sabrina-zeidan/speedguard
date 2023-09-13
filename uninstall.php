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
	$args          = [
		'post_type'      => 'guarded-page',
		'posts_per_page' => - 1,
		'post_status'    => 'any',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	];
	$the_query     = new WP_Query( $args );
	$guarded_pages = $the_query->get_posts();
	if ( $guarded_pages ) :
		foreach ( $guarded_pages as $guarded_page_id ) {
			wp_delete_post( $guarded_page_id, true );
		}
	endif;
	wp_reset_postdata();

	// Delete posts meta
	$args          = [
		'post_type'      => 'any',
		'posts_per_page' => - 1,
		'post_status'    => 'any',
		'fields'         => 'ids',
		'meta_query'     => [
			'relation' => 'AND',
			[
				'key'     => 'speedguard_on',
				'compare' => 'EXISTS',
			],
		],
		'no_found_rows'  => true,
	];
	$the_query     = new WP_Query( $args );
	$guarded_posts = $the_query->get_posts();
	if ( $guarded_posts ) :
		foreach ( $guarded_posts as $guarded_post_id ) {
			delete_post_meta( $guarded_post_id, 'speedguard_on' );
		}
	endif;
	wp_reset_postdata();

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
	if ( count( $the_terms ) > 0 ) {
		foreach ( $the_terms as $term_id ) {
			delete_term_meta( $term_id, 'speedguard_on' );
		}
	}

	// Delete options
	$speedguard_options = [ 'speedguard_options', 'speedguard_api', 'speedguard_average' ];
	foreach ( $speedguard_options as $option_name ) {
		delete_option( $option_name );
		if ( is_multisite() ) {
			delete_site_option( $option_name );
		}
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
	}//endforeach
}//endif network
else {
	speedguard_delete_data();
}
