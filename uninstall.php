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
	//Delete options
	$speedguard_options = array ('speedguard_options','speedguard_api','speedguard_average');
		foreach ($speedguard_options as $option_name){
			delete_option($option_name);
		}
	//Delete CRON events 
	 $speedguard_events = array ('speedguard_update_results','speedguard_rate_this_plugin','speedguard_email_test_results');
		foreach ($speedguard_events as $speedguard_event){
			wp_clear_scheduled_hook($speedguard_event);
		}

	//Delete cpt 
		$args = array(
			'post_type'      => 'guarded-page',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields' => 'ids',
		);
		$guarded_pages = get_posts( $args );
		foreach ($guarded_pages as $guarded_page_id){
			wp_delete_post( $guarded_page_id, true);  
		}
	//Delete post_meta
		$args = array(
			'post_type'      => 'any',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields' => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'speedguard_on',
					'compare' => 'EXISTS',
				),
			),
		);
		$guarded_posts = get_posts( $args );
		foreach ($guarded_posts as $guarded_post_id){
		delete_post_meta($guarded_post_id, 'speedguard_on');
		}


