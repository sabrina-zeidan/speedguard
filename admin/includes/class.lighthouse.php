<?php

/**
 *
 *   Class responsible for handling tests via Lighthouse
 */
class SpeedGuard_Lighthouse {

	// v5 https://developers.google.com/speed/docs/insights/v5/get-started
	function __construct() {
	}

	/** Perform a New Test -- Test both Desktop and Mobile once request to test is made, save PSI, CWV and CWV for origin */
	public static function lighthouse_new_test( $guarded_page_id ) {
		// debugging https://developers.google.com/speed/docs/insights/v5/reference/pagespeedapi/runpagespeed?apix=true&apix_params=%7B%22url%22%3A%22https%3A%2F%2Fsabrinazeidan.com%22%7D#try-it
		$guarded_page_url = get_post_meta( $guarded_page_id, 'speedguard_page_url', true );
		$devices    = [ 'desktop', 'mobile' ];
		$cwv_origin = [];

		$both_devices_values = []; //for post_meta sg_test_result
		foreach ( $devices as $device ) {
			sleep( 1 ); // So we can use LightHouse without API
			$request  = add_query_arg(
				array(
					'url'      => $guarded_page_url,
					'category' => 'performance',
					'strategy' => $device,
				),
				'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
			);
			$args     = array( 'timeout' => 30 );
			$response = wp_safe_remote_get( $request, $args );
			if ( is_wp_error( $response ) ) { // if no response
				return false;
			}
			$response      = wp_remote_retrieve_body( $response );
			$json_response = json_decode( $response, true, 1512 );

			// If test has PSI results (request was successful)
			if ( ! empty( $json_response['lighthouseResult'] ) ) {
				// Save PSI and CWV together to meta sg_test_result as device array
			//	$device_values        = [];
				$device_values['psi'] = [
					'lcp' => $json_response['lighthouseResult']['audits']['largest-contentful-paint'],
					// title, description, score, scoreDisplayMode, displayValue, numericValue
					'cls' => $json_response['lighthouseResult']['audits']['cumulative-layout-shift'],
				];
				// TODO -- check if not available?
				$device_values['cwv'] = [
					'lcp' => $json_response['loadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS'],
					// percentile, distributions, category
					'cls' => $json_response['loadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE'],
					'fid' => $json_response['loadingExperience']['metrics']['FIRST_INPUT_DELAY_MS'],
				];
				$both_devices_values[$device] = $device_values;

				// Prepare data for side-wide widget
				// If site has Origin CWV data -- save it
				//TODO format this to be universal
				if ( ! empty( $json_response['originLoadingExperience'] ) ) {
					// CWV Origin for this Device

					$notavailable = "N/A";
					$LCP = isset($json_response['originLoadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS']) ? $json_response['originLoadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS']: $notavailable ; // percentile,distributions, category
					$CLS = isset($json_response['originLoadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE']) ? $json_response['originLoadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE'] : $notavailable; // percentile,distributions, category
					$FID = isset($json_response['originLoadingExperience']['metrics']['FIRST_INPUT_DELAY_MS'])? $json_response['originLoadingExperience']['metrics']['FIRST_INPUT_DELAY_MS']: $notavailable; // percentile,distributions, category


				}
				else{ //if no sidewide CWV available
					$origin_cwv[ $device ] = "N/A";
				}
				$origin[ $device ] ['cwv'] = [
					'lcp' => $LCP,
					'cls' => $CLS,
					'fid' => $FID,
				];

			} else {
				// If no PSI data -- meaning test failed to execute
				//TODO -- add error message
			}
		}

		// Create a new test CPT
		$new_test_cpt     = array(
			'ID'         => $guarded_page_id,
			'post_title' => $guarded_page_url,
		);
		wp_update_post( $new_test_cpt  );
		//And save all data
		SpeedGuard_Admin::update_this_plugin_option( 'sg_origin_result', $origin );
		//TODO move site average PSI to here?
		$updated = update_post_meta( $guarded_page_id, 'sg_test_result', $both_devices_values);
		return $updated;
	}
}

new SpeedGuard_Lighthouse();
