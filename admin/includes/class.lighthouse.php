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

		$devices = ['desktop', 'mobile'];
		$cwv_origin = [];
		foreach ( $devices as $device ) {
			sleep( 3 ); // So we can use LightHouse without API
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
			if ( is_wp_error( $response ) ) { //if no response
				return false;
			}
			$response      = wp_remote_retrieve_body( $response );
			$json_response = json_decode( $response, true, 1512 );

			// If test has PSI results:
			if ( ! empty( $json_response['lighthouseResult'] ) ) {
				//Save PSI and CWV together by device to meta sg_mobile and sg_desktop
				$device_values        = [];
				$device_values['psi'] = [
					'lcp' => $json_response['lighthouseResult']['audits']['largest-contentful-paint'],
					// title, description, score, scoreDisplayMode, displayValue, numericValue
					'cls' => $json_response['lighthouseResult']['audits']['cumulative-layout-shift']
				];
				// TODO -- check if not available?
				$device_values['cwv'] = [
					'lcp' => $json_response['loadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS'],
					// percentile, distributions, category
					'cls' => $json_response['loadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE'],
					'fid' => $json_response['loadingExperience']['metrics']['FIRST_INPUT_DELAY_MS'],
				];
//This works for the last one only -- TODO for both or it's ok?
				$updated = update_post_meta( $guarded_page_id, 'sg_' . $device, $device_values );

				//TODO review this -- is needed?
				//update_post_meta( $guarded_page_id, 'speedguard_page_connection', $device ); //TODO  remove
				//$code        = $guarded_page_url . '|' . $device;
				$code        = $guarded_page_url;
				$my_post     = array(
					'ID'         => $guarded_page_id,
					'post_title' => $code,
				);
				$update_post = wp_update_post( $my_post );


				//TODO update with both mobile and desktop
				//$updated = update_post_meta( $guarded_page_id, 'sg_mobile', $lcp );
				//TODO retrieve and update (if mobile runs after desktop for example
				//		return $updated; //TODO updated when both updated

				//If site has Origin CWV data -- save it
				if ( ! empty( $json_response['originLoadingExperience'] ) ) {
					//CWV Origin for this Device -- TODO
					$LCP                   = $json_response['originLoadingExperience']['metrics']['LARGEST_CONTENTFUL_PAINT_MS']; // percentile,distributions, category
					$CLS                   = $json_response['originLoadingExperience']['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE']; // percentile,distributions, category
					$FID                   = $json_response['originLoadingExperience']['metrics']['FIRST_INPUT_DELAY_MS']; // percentile,distributions, category
					$cwv_origin[ $device ] = [
						'lcp' => $LCP,
						'cls' => $CLS,
						'fid' => $FID
					];
					// Save Origin CWV
					SpeedGuard_Admin::update_this_plugin_option( 'speedguard_cwv_origin', $cwv_origin );
				}

			}
			else { //If no PSI data
				// TODO error handling if origin CWV is not available
			}
		}

		return $updated;// TODO
	}




}

new SpeedGuard_Lighthouse();
