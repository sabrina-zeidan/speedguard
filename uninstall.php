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
	///Delete options
	$speedguard_options = array ('speedguard_options','speedguard_api','speedguard_average');
		foreach ($speedguard_options as $option_name){
			if  ( is_multisite()) delete_site_option($option_name);
			else delete_option($option_name);
		}
		
	//Delete cpt 
	if  ( is_multisite()) switch_to_blog(get_network()->site_id);
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
	if  ( is_multisite()) restore_current_blog();
	
	//Delete post_meta
	if  ( is_multisite()) {     
				$sites = get_sites();				
				foreach ($sites as $site ) {
					$blog_id = $site->blog_id;				
						switch_to_blog( $blog_id );
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
						restore_current_blog();		
				}//endforeach				
	}
	else { 
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
	}


