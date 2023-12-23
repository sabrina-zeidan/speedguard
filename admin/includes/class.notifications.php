<?php

/**
 *
 *   Class responsible for email notifications
 */
class SpeedGuard_Notifications {

	function __construct() {
	}

	public static function test_results_email( $type ) {
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		// Check if there are any tests running at the moment, and reschedule if so
		if ( get_transient( 'speedguard-tests-running' ) ) {
			wp_schedule_single_event( time() + 10 * 60, 'speedguard_email_test_results' );
			return;
		}
        $guarded_pages = get_transient('speedguard_tests_count');
var_dump($guarded_pages);
        $speedguard_cwv_origin = SpeedGuard_Admin::get_this_plugin_option( 'sg_origin_results' );
        echo "<pre>";
        var_dump($speedguard_cwv_origin);
        echo "</pre>";
        if ( (int)$guarded_pages > 0) { //if there are monitored pages
			$speedguard_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
			$admin_email        = $speedguard_options['email_me_at'];
			$site_url           = wp_parse_url( get_home_url() );
			$site_url           = $site_url['host'];

			$status = ''; //TODO 1

			$subject            = sprintf( __( 'Performance update for %1$s', 'speedguard' ), $site_url );

			$message = '';
			$message .= '<!DOCTYPE html>';
			$message .= '<html>';
			$message .= '<head>';
			$message .= '<title>';
			$message .= __( 'SpeedGuard Report', 'speedguard' );
			$message .= '</title>';
			$message .= '<style>';
			$message .= 'table {border-collapse: collapse;width: 560px; margin-top: 2em;}';
			$message .= 'th, td {text-align: left; padding: 8px;}';
			$message .= 'tr:nth-child(even) {background-color: #f2f2f2;}';
			$message .= '</style>';
			$message .= '</head>';
			$message .= '<body style="padding-top: 50px; padding-bottom: 50px; background:#fff; color:#000;">';
			$message .= '<table align="center">';
			$message .= '<tr>';
			$message .= '<td style="padding: 10px; bgcolor="#f7f7f7">';
			$message .= '<p style="text-align:center; font-size: 1.2em; font-weight: bold;">';
			$message .= __( 'Core Web Vitals report', 'speedguard' );
			$message .= '</p>';
			$message .= '<p>';
			$message .= sprintf( __( 'Currently the website %1$s is %2$s Core Web Vitals assessment by Google. This result is for Origin, meaning for the website in general.', 'speedguard' ), $site_url, $status);
            $message .= '<p>';
            $message .=  __( 'Individual URLs might be passing or not.');
			$message .= '</p>';
			$message .= '<p>';
	        $message .= sprintf( __( '%1$s pages are monitored now.', 'speedguard'), $guarded_pages );
			$message .= '</p>';
			$message .= '<p>';
			$message .= sprintf( __( 'You can see the detailed report and add more individual URLs to be monitored %1$shere%2$s.', 'speedguard' ), '<a href="' . SpeedGuard_Admin::speedguard_page_url( "tests" ) . '" target="_blank">', '</a>' );
			$message .= '</p>';
			$message .= '</td>';
			$message .= '</tr>';
			$message .= '<tr>';
			$message .= '<td width="100%" style="padding: 0;">';
			$message .= '<div style="padding: 1em; color:#000;">';
			$message .= '<p style="font-size: 1.2em; font-weight: bold;">';
			$message .= __( 'Important questions:', 'speedguard' );
			$message .= '</p>';
	//	$message .= SpeedGuard_Widgets::important_questions_widget_function_return();  // TODO: Address the replacement issue
			$message .= '</div>';
			$message .= '</td>';
			$message .= '</tr>';
			$message .= '<tr>';
			$message .= '<td style="padding: 10px;color:#5f5a5a; text-align:right; font-size: 0.9em;" bgcolor="#e6e1e1" align="right">';
			$message .= sprintf( __( 'This report was requested by administrator of %1$s', 'speedguard' ), $site_url );
			$message .= '. ';
			$message .= sprintf( __( 'You can change SpeedGuard notification settings %1$shere%2$s any time.', 'speedguard' ), '<a href="' . SpeedGuard_Admin::speedguard_page_url( 'settings' ) . '" target="_blank">', '</a>' );
			$message .= '</td>';
			$message .= '</tr>';
			$message .= '</table>';
			$message .= '</body>';
			$message .= '</html>';
			echo $message;
			//wp_mail( $admin_email, $subject, $message, $headers );


		}
	}
}

new SpeedGuard_Notifications();
