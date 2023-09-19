<?php

/**
 *
 *   Class responsible for handling tests via Lighthouse
 */
class SpeedGuard_Lighthouse {

	// v5 https://developers.google.com/speed/docs/insights/v5/get-started
	function __construct() {
		//Recount PSI Average for Origin when test is deleted
		add_action( 'deleted_post_meta', [ $this, 'count_average_psi' ], 10, 4 );
	}

	/** Perform a New Test -- Test both Desktop and Mobile once request to test is made, save PSI, CWV and CWV for origin */
	public static function lighthouse_new_test( $guarded_page_id ) {
		$guarded_page_url = get_post_meta( $guarded_page_id, 'speedguard_page_url', true );
		$devices          = [ 'desktop', 'mobile' ];

		$origin = [];

		$both_devices_values = []; //for post_meta sg_test_result
		foreach ( $devices as $device ) {
			//sleep( 5 ); // So we can use LightHouse without API
			$request  = add_query_arg(
				[
					'url'      => $guarded_page_url,
					'category' => 'performance',
					'strategy' => $device,
				],
				'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
			);
			$args     = [ 'timeout' => 30 ];
			$response = wp_safe_remote_get( $request, $args );
			if ( is_wp_error( $response ) ) { // if no response
				return false;
			}
			$response      = wp_remote_retrieve_body( $response );
			$json_response = json_decode( $response, true, 1512 );

			// If test has PSI results (request was successful)
			if ( ! empty( $json_response['lighthouseResult'] ) ) {
				// Save PSI and CWV together to meta sg_test_result as device array
				$device_values['psi']           = [
					'lcp' => $json_response['lighthouseResult']['audits']['largest-contentful-paint'],
					// title, description, score, scoreDisplayMode, displayValue, numericValue
					'cls' => $json_response['lighthouseResult']['audits']['cumulative-layout-shift'],
				];
				$device_values['cwv']           = [
					'lcp' => $json_response['loadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS'],
					// percentile, distributions, category
					'cls' => $json_response['loadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE'],
					'fid' => $json_response['loadingExperience']['metrics']['FIRST_INPUT_DELAY_MS'],
				];
				$both_devices_values[ $device ] = $device_values;

				// Save CWV for origin for this Device
				if ( ! empty( $json_response['originLoadingExperience'] ) ) {
					$notavailable = "N/A";
					$LCP          = isset( $json_response['originLoadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS'] ) ? $json_response['originLoadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS'] : $notavailable; // percentile,distributions, category
					$CLS          = isset( $json_response['originLoadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE'] ) ? $json_response['originLoadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE'] : $notavailable; // percentile,distributions, category
					$FID          = isset( $json_response['originLoadingExperience']['metrics']['FIRST_INPUT_DELAY_MS'] ) ? $json_response['originLoadingExperience']['metrics']['FIRST_INPUT_DELAY_MS'] : $notavailable; // percentile,distributions, category

					$origin[ $device ] ['cwv'] = [
						'lcp' => $LCP,
						'cls' => $CLS,
						'fid' => $FID,
					];
				} else {
					$origin[ $device ]['cwv'] = "no CWV available"; // No sidewide CWV available
				}
			} else {
				// TODOIf no PSI data -- meaning test failed to execute -- add error message
			}
		}

		// Create a new test CPT
		$new_test_cpt = [
			'ID'         => $guarded_page_id,
			'post_title' => $guarded_page_url,
		];
		wp_update_post( $new_test_cpt );

		$updated = update_post_meta( $guarded_page_id, 'sg_test_result', $both_devices_values );

		//And save all data
		//Save CWV for origin
		SpeedGuard_Admin::update_this_plugin_option( 'sg_origin_results', $origin );

		return $updated;
	}

	public static function update_average_psi() {
		$new_average_array    = SpeedGuard_Lighthouse::count_average_psi();
		$origin               = SpeedGuard_Admin::get_this_plugin_option( 'sg_origin_results' );
		if (is_array($origin)) {
			$new_sg_origin_result = array_merge_recursive( $origin, $new_average_array );
			SpeedGuard_Admin::update_this_plugin_option( 'sg_origin_results', $new_sg_origin_result );
		}
	}

	public static function count_average_psi() {
		// Prepare new values for PSI Averages
		$new_average_array = [];
		//	if (! get_transient('speedguard-tests-running')) { TODO adjust the orfder to make calculcations run only on last test
		// Get all tests with valid results
		$guarded_pages = get_posts( [
			'posts_per_page' => 100,
			'no_found_rows'  => true,
			'post_type'      => SpeedGuard_Admin::$cpt_name,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => 'sg_test_result',
					'value'   => 'waiting',
					'compare' => 'NOT LIKE',
				]
			]
		] );

		// If there are no tests with valid results, return an empty array
		if ( empty( $guarded_pages ) ) {
			return [];
		}

		// Initialize the average array
		$average = [];

		// Loop through the guarded pages
		foreach ( $guarded_pages as $guarded_page ) {
			// Get the guarded page load time
			$guarded_page_load_time = get_post_meta( $guarded_page, 'sg_test_result', true );

			// Loop through the device types
			foreach ( SpeedGuard_Admin::SG_METRICS_ARRAY as $device => $test_types ) {
				// Loop through the test types
				foreach ( $test_types as $test_type => $metrics ) {
					// If the test type is PSI, prepare the metrics
					if ( $test_type === 'psi' ) {
						foreach ( $metrics as $metric ) {
							// Add the guarded page load time to the average array
							$average[ $device ][ $test_type ][ $metric ]['guarded_pages'][ $guarded_page ] = $guarded_page_load_time[ $device ][ $test_type ][ $metric ]['numericValue'];
						}
					}
				}
			}
		}

		// Loop through the average array
		foreach ( $average as $device => $test_types ) {
			// Loop through the test types
			foreach ( $test_types as $test_type => $metrics ) {
				// Loop through the metrics
				foreach ( $metrics as $metric => $values ) {
					// Calculate the average
					$average = array_sum( $values['guarded_pages'] ) / count( $values['guarded_pages'] );

					// Create a new metric array
					$new_metric_array = [
						'average' => $average,
					];

					// If the metric is LCP, calculate the display value and score
					if ( $metric === 'lcp' ) {
						$new_metric_array['displayValue'] = round( $average / 1000, 2 ) . ' s';

						if ( $average < 2.5 ) {
							$new_metric_array['score'] = 'FAST';
						} elseif ( $average < 4.0 ) {
							$new_metric_array['score'] = 'AVERAGE';
						} else {
							$new_metric_array['score'] = 'SLOW';
						}
					}

					// If the metric is CLS, calculate the display value and score
					if ( $metric === 'cls' ) {
						$new_metric_array['displayValue'] = round( $average, 3 );

						if ( $average < 0.1 ) {
							$new_metric_array['score'] = 'FAST';
						} elseif ( $average < 0.25 ) {
							$new_metric_array['score'] = 'AVERAGE';
						} else {
							$new_metric_array['score'] = 'SLOW';
						}
					}

					// Add the new metric array to the new average array
					$new_average_array[ $device ][ $test_type ][ $metric ] = $new_metric_array;
				}
			}
		}
		//	}
		// Return the new average array
		return $new_average_array;
	}

}


new SpeedGuard_Lighthouse();
