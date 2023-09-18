<?php

/**
 *
 *   Class responsible for email notifications
 */
class SpeedGuard_Notifications {

	function __construct() {
	}

	//TOTAL TODO
	public static function test_results_email( $type ) {
		// Check if there are any tests running at the moment, and reschedule if so
		if ( get_transient( 'speedguard-tests-running' ) ) {
			wp_schedule_single_event( time() + 10 * 60, 'speedguard_email_test_results' );

			return;
		}
		$guarded_pages = get_posts( [
			'post_type'      => SpeedGuard_Admin::$cpt_name,
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );
		if ( $guarded_pages ) { //if there are monitored pages
			$speedguard_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
			$admin_email        = $speedguard_options['email_me_at'];
			$site_url           = parse_url( get_home_url() );
			$site_url           = $site_url['host'];
			$subject            = sprintf( __( 'Performance update for %1$s', 'speedguard' ), $site_url );
			ob_start();
			echo '<html><head>
								<title>' . __( 'SpeedGuard Report', 'speedguard' ) . '</title>
								<style>
								table {border-collapse: collapse;width: 560px; margin-top: 2em;}
								th, td {text-align: left; padding: 8px;	}
								tr:nth-child(even) {background-color: #f2f2f2;}
								</style>
								</head>
										<body style="padding-top: 50px; padding-bottom: 50px;  background:#fff; color:#000;" >
											<table align="center">  
												<tr>
													<td style="padding: 10px;" bgcolor="#f7f7f7"><p style="text-align:center; font-size: 1.2em; font-weight: bold;" >' . __( 'Performance report', 'speedguard' ) . '</p>
													
													<p>												
													' . sprintf( __( 'Core Web Vitals for the entire website:', 'speedguard' ) ) . '</p>
													
													<p>												
													' . sprintf( __( 'Currently %3$ pages are monitored. You can see the  %1$sdetailed report here%2$ and also add more pages to be tracked there.', 'speedguard' ), '<a href="' . SpeedGuard_Admin::speedguard_page_url( 'tests' ) . '" target="_blank">', '</a>', count( $guarded_pages ) ) . '</p>
													
													<p>' . SpeedGuard_Widgets::origin_results_widget_function() . '																			
										</p>
																				
													</td> 
												</tr>
												<tr> 
													<td width="100%" style="padding: 0;">';
			if ( isset( $note ) ) {
				echo '<br>' . $note;
			}
			echo '<table>  
														<thead><tr style="border: 1px solid #ccc;" >
														<td>' . __( 'URL', 'speedguard' ) . '</td>
														<td>' . __( 'Load time', 'speedguard' ) . '</td>													
														</tr></thead><tbody>';
			echo '</tbody>
														</table>													
														<div style="padding: 1em; color:#000;"> 
														<p style="font-size: 1.2em; font-weight: bold;" >' . __( 'Important questions:', 'speedguard' ) . '</p>';
			str_replace( 'utm_medium=sidebar', 'utm_medium=email_report', SpeedGuard_Widgets::important_questions_widget_function() ); // TODO: is not replaced
			echo '
														</div>
													</td> 
												</tr>
												<tr>
													<td style="padding: 10px;color:#5f5a5a; text-align:right; font-size: 0.9em;" bgcolor="#e6e1e1" align="right">' . sprintf( __( 'This report was requested by administrator of %1$s', 'speedguard' ), $site_url ) . '. ' . sprintf( __( 'You can change SpeedGuard notification settings %1$shere%2$s any time.', 'speedguard' ), '<a href="' . SpeedGuard_Admin::speedguard_page_url( 'settings' ) . '" target="_blank">', '</a>' ) . '</td>
												</tr>
											</table>
										</body>
									</html>';
			$message = ob_get_contents();
			ob_end_clean();
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			wp_mail( $admin_email, $subject, $message, $headers );
		}
	}
}

new SpeedGuard_Notifications();
