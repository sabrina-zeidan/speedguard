<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://sabrinazeidan.com/
 * @since      1.0.0
 *
 * @package    Speedguard
 * @subpackage Speedguard/includes
 */

/**
 * Fired during plugin deactivation. 
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Speedguard
 * @subpackage Speedguard/includes
 * @author     Sabrina Zeidan <sabrinazeidan@gmail.com>
 */
class Speedguard_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() { 
	set_transient( 'speedguard-notice-deactivation', true,  12*60 ); 
	}

}
