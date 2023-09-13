<?php

/**
 * Fired during plugin activation.
 *
 * @see       http://sabrinazeidan.com/
 * @since      1.0.0
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 *
 * @author     Sabrina Zeidan <sabrinazeidan@gmail.com>
 */
class Speedguard_Activator {

	public static function activate() {
		set_transient( 'speedguard-notice-activation', true, 20 );
		$add_homepage = SpeedGuard_Tests::try_add_speedguard_test( get_site_url() );
	}
}
