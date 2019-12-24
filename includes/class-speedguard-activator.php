<?php

/**
 * Fired during plugin activation
 *
 * @link       http://sabrinazeidan.com/
 * @since      1.0.0
 *
 * @package    Speedguard
 * @subpackage Speedguard/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Speedguard
 * @subpackage Speedguard/includes
 * @author     Sabrina Zeidan <sabrinazeidan@gmail.com>
 */
class Speedguard_Activator {
	public static function activate() {
		
	
	$api_key = Speedguard_Admin::get_this_plugin_option('speedguard_api');
			if (empty($api_key)){
			Speedguard_Admin::update_this_plugin_option('speedguard_api', array('api_key' => ''));
		}
		set_transient( 'speedguard-notice-activation', true, 5 );





		

	}			
			
}	 

