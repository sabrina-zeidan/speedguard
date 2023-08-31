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
class Speedguard_Deactivator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        //Delete CRON events
        $speedguard_events = array ('speedguard_update_results','speedguard_rate_this_plugin','speedguard_email_test_results');
        foreach ($speedguard_events as $speedguard_event) {
            wp_clear_scheduled_hook($speedguard_event);
        }
        
        set_transient('speedguard-notice-deactivation', true, 12*60);
    }
}
