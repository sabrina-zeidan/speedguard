<?php
/**
 *
 *   Class responsible for adding metaboxes
 */
function delete_transients_with_prefix( $prefix ) {
	foreach ( get_transient_keys_with_prefix( $prefix ) as $key ) {
		// delete_transient( $key );
		echo "<br>" . $key . " " . get_transient( $key );
	}
}

/**
 * Gets all transient keys in the database with a specific prefix.
 *
 * Note that this doesn't work for sites that use a persistent object
 * cache, since in that case, transients are stored in memory.
 *
 * @param string $prefix Prefix to search for.
 *
 * @return array          Transient keys with prefix, or empty array on error.
 */
function get_transient_keys_with_prefix( $prefix ) {
	global $wpdb;
	$prefix = $wpdb->esc_like( '_transient_' . $prefix );
	$sql    = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
	$keys   = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );
	if ( is_wp_error( $keys ) ) {
		return [];
	}

	return array_map( function ( $key ) {
		// Remove '_transient_' from the option name.
		return substr( $key['option_name'], strlen( '_transient_' ) );
	}, $keys );
}

//To fire the function:
add_action( 'wp_head', 'sz_output' );
function sz_output() {
	delete_transients_with_prefix( 'speedguard' );
}

class SpeedGuard_Widgets {
	public function __construct() {
		$options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		if ( ! empty( $options ) ) {
			if ( $options['show_dashboard_widget'] === 'on' ) {
				add_action( 'wp_' . ( defined( 'SPEEDGUARD_MU_NETWORK' ) ? 'network_' : '' ) . 'dashboard_setup', [
						$this,
						'speedguard_dashboard_widget_function',
					] );
			}
			if ( ( 'on' === $options['show_ab_widget'] ) && ! is_admin() ) {
				add_action( 'admin_bar_menu', [ $this, 'speedguard_admin_bar_widget' ], 710 );
			}
		}
	}

	/*
	Function responsible for displaying widget with PSI Average results widget on the admin dashboard
	 *
	 * @param $post
	 * @param $args
	*/
	/**
	 * Define all metaboxes fro plugin's admin pages (Tests and Settings)
	 */
	public static function add_meta_boxes() {
		$sg_test_type = SpeedGuard_Settings::global_test_type();
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		add_meta_box( 'settings-meta-box', __( 'SpeedGuard Settings', 'speedguard' ), [
				'SpeedGuard_Settings',
				'settings_meta_box',
			], '', 'normal', 'core' );
		if ( 'cwv' === $sg_test_type ) {
			$origin_widget_title = 'Core Web Vitals (real users experience) for the entire website';
		} elseif ( 'psi' === $sg_test_type ) {
			$origin_widget_title = 'PageSpeed Insights (lab tests)';
		}
		add_meta_box( 'speedguard-dashboard-widget', __( $origin_widget_title, 'speedguard' ), [
				'SpeedGuard_Widgets',
				'origin_results_widget_function',
			], '', 'main-content', 'core' );
		add_meta_box( 'speedguard-add-new-url-meta-box', __( 'Add new URL to monitoring', 'speedguard' ), [
				'SpeedGuard_Widgets',
				'add_new_widget_function',
			], '', 'main-content', 'core' );
		$sg_test_type = SpeedGuard_Settings::global_test_type();
		if ( 'cwv' === $sg_test_type ) {
			$test_type = ' -- Core Web Vitals';
		} elseif ( 'psi' === $sg_test_type ) {
			$test_type = ' -- PageSpeed Insights';
		}
		add_meta_box( 'tests-list-meta-box', sprintf( __( 'Test results for specific URLs %s', 'speedguard' ), $test_type ), [
				'SpeedGuard_Tests',
				'tests_results_widget_function',
			], '', 'main-content', 'core' );
		add_meta_box( 'speedguard-legend-meta-box', __( 'How to understand the information above?', 'speedguard' ), [
				'SpeedGuard_Widgets',
				'explanation_widget_function',
			], '', 'main-content', 'core' );
		add_meta_box( 'speedguard-important-questions-meta-box', __( 'Important questions:', 'speedguard' ), [
				'SpeedGuard_Widgets',
				'important_questions_widget_function',
			], '', 'side', 'core' );
		add_meta_box( 'speedguard-about-meta-box', __( 'Do you like this plugin?', 'speedguard' ), [
				'SpeedGuard_Widgets',
				'about_widget_function',
			], '', 'side', 'core' );
	}

	/**
	 * Function responsible for displaying the Origin widget, both n Tests page and Dashboard
	 */
	public static function origin_results_widget_function( $post = '', $args = '' ) {
		// Retrieving data to display
		delete_transients_with_prefix( 'speedguard' );
		//echo SpeedGuard_Notifications::test_results_email( 'regular' );
		$speedguard_cwv_origin = SpeedGuard_Admin::get_this_plugin_option( 'sg_origin_results' );
		// Preparing data to display
		// TODO make this constant
		$sg_test_type = SpeedGuard_Settings::global_test_type();
		foreach ( SpeedGuard_Admin::SG_METRICS_ARRAY as $device => $test_types ) {
			foreach ( $test_types as $test_type => $metrics ) {
				if ( $test_type === $sg_test_type ) { //prepare metrics only for needed test type
					foreach ( $metrics as $metric ) {
						$current_metric  = $device . '_' . $metric;
						$$current_metric = SpeedGuard_Widgets::single_metric_display( $speedguard_cwv_origin, $device, $test_type, $metric );
					}
				}
			}
		}
		if ( 'cwv' === $sg_test_type ) {
			$fid_tr = '<tr><th>' . __( 'First Input Delay (FID)', 'speedguard' ) . '</th>
	<td>' . $mobile_fid . '</td>
	<td>' . $desktop_fid . '</td></tr>';
		} else {
			$fid_tr = '';
		}
        if ( isset($speedguard_cwv_origin['desktop']['cwv']['overall_category']) && isset($speedguard_cwv_origin['mobile']['cwv']['overall_category']) ) {

	        $overall_category_desktop = $speedguard_cwv_origin['desktop']['cwv']['overall_category'];
	        $overall_category_mobile  = $speedguard_cwv_origin['mobile']['cwv']['overall_category'];
            //overall_category can be FAST, AVERAGE, SLOW. Assign color (red, yellow, green) accordingly
	        $mobile_color  = ( $overall_category_mobile === 'FAST' ) ? 'score-green' : ( ( $overall_category_mobile === 'AVERAGE' ) ? 'score-yellow' : 'score-red' );
	        $desktop_color = ( $overall_category_desktop === 'FAST' ) ? 'score-green' : ( ( $overall_category_desktop === 'AVERAGE' ) ? 'score-yellow' : 'score-red' );
        }
        $mobile_color = isset($mobile_color)?$mobile_color:'';
        $desktop_color = isset($desktop_color)?$desktop_color:'';
		$content       = "
	<table class='widefat fixed striped toplevel_page_speedguard_tests_cwv_widget'>
	<thead>
	<tr class='bc-platforms'><td></td>
	<th><i class='sg-device-column mobile speedguard-score " . $mobile_color . "' aria-hidden='true' title='Mobile'></i></th>
	<th><i class='sg-device-column desktop speedguard-score " . $desktop_color . "' aria-hidden='true' title='Desktop''></i></th>
	</tr>
	</thead>
	<thbody>
	<tr><th>" . __( 'Largest Contentful Paint (LCP)', 'speedguard' ) . '</th>
	<td>' . $mobile_lcp . '</td>
	<td>' . $desktop_lcp . '</td>
	</tr>                                                                   
	<tr><th>' . __( 'Cumulative Layout Shift (CLS)', 'speedguard' ) . '</th>
	<td>' . $mobile_cls . '</td>
	<td>' . $desktop_cls . '</td>
	</tr>
	' . $fid_tr . '
	</thbody>
	</table>
	';
		if ( ( 'cwv' === $sg_test_type ) && str_contains( $mobile_lcp, 'N' ) ) {
			$info_text = sprintf( __( 'N/A means that there is no data from Google available -- most likely your website have not got enough traffic for Google to make evaluation (Not enough usage data in the last 90 days for this device type)', 'speedguard' ), '<a href="#">', '</a>' ) . '<div><br></div>';
		} elseif ( 'psi' === $sg_test_type ) {
			$info_text = sprintf( __( 'This is not real user data. These are averages calculated based on the tests below. Core Web Vitals -- is where the the real data is. You can switch in Settings', 'speedguard' ), '<a href="#">', '</a>' ) . '<div><br></div>';
		} else {
			$info_text = '';
		}
		echo $content . $info_text;
	}

	/**
	 * Function responsible for formatting CWV data for display
	 */
	public static function single_metric_display( $results_array, $device, $test_type, $metric ) {
		$display_value = '';
		$category      = '';
		$class         = '';
		if ( ( $results_array === 'waiting' ) ) {  // tests are currently running, //PSI Origin results will be calculated after all tests are finished
			$class = 'waiting';
		} elseif ( ( is_array( $results_array ) ) ) {// tests are not currently running
			// Check if metric data is available for this device
			if ( isset( $results_array[ $device ][ $test_type ][ $metric ] ) ) {
				if ( $test_type === 'psi' ) {
					$display_value = $results_array[ $device ][ $test_type ][ $metric ]['displayValue'];
					$class         = 'score';
					$category      = $results_array[ $device ][ $test_type ][ $metric ]['score'];
				} elseif ( $test_type === 'cwv' ) {
					$metrics_value = $results_array[ $device ][ $test_type ][ $metric ]['percentile'];
					// Format metrics output for display
					if ( $metric === 'lcp' ) {
						$display_value = round( $metrics_value / 1000, 2 ) . ' s';
					} elseif ( $metric === 'cls' ) {
						$display_value = $metrics_value;
						$display_value = $metrics_value / 100;
					} elseif ( $metric === 'fid' ) {
						$display_value = $metrics_value . ' ms';
					}
					$class    = 'score';
					$category = $results_array[ $device ][ $test_type ][ $metric ]['category'];
				}
			} elseif ( $test_type === 'psi' && get_transient( 'speedguard-tests-running' ) ) {
				$class = 'waiting';
			} else {
				// No data aailable for the metric
				$class         = 'na';
				$display_value = 'N/A';
			}
		}
		$category             = 'data-score-category="' . $category . '"';
		$class                = 'class="speedguard-' . $class . '"';
		$metric_display_value = '<span ' . $category . ' ' . $class . '>' . $display_value . '</span>';

		return $metric_display_value;
	}

	public static function explanation_widget_function() {
		$cwv_link = 'https://web.dev/lcp/';
		// Create the table.
		?>


        <ul>
            <li>
                <h3><?php _e( 'What does N/A mean?' ); ?></h3>
                <span>
			<?php
			echo sprintf( __( 'If you see "N/A" for a metric in Core Web Vitals tests, it means that there is not enough real-user data to provide a score. This can happen if your website is new or has very low traffic. The same will be displayed in your <a href="%1$s">Google Search Console (GSC)</a>, which uses the same data source (<a href="%2$s">CrUX report</a>) as CWV.', 'speedguard' ), esc_url( 'https://search.google.com/search-console/' ), esc_url( 'https://developer.chrome.com/docs/crux/' ), );
			?>

		</span>
            </li>
            <li>
                <h3><?php _e( 'What is the difference between Core Web Vitals and PageSpeed Insights?' ); ?></h3>
                <span>
			<?php _e( 'The main difference between CWV and PSI is that CWV is based on real-user data, while PSI uses lab data collected in a controlled environment. Lab data can be useful for debugging performance issues, but it is not as representative of the real-world user experience as real-user data.' ); ?>
			<p><strong> 
					<?php _e( 'If you have CWV data available, you should always refer to that data first, as it represents the real experience real users of your website are having.' ); ?></strong></p>
			<?php _e( 'If there is no CWV data avalable -- you CAN use PSI as a reference, but you need to remember these are LAB tests: on the devices, connection and location that are most certainlely don\'t match the actual state of things.' ); ?>
		</span>
            </li>
            <li>
                <h3><?php _e( 'Understanding metrics:' ); ?></h3>
                <span>
			<p>

				<?php _e( '<strong>Largest Contentful Paint (LCP):</strong> The time it takes for the largest content element on a page to load. This is typically an image or video.' ); ?>
				<img src="<?php echo plugin_dir_url( __DIR__ ) . 'assets/images/lcp.svg' ?>"
                     alt="Largest Contentful Paint chart">
			</p>
			<p>
				<?php _e( '<strong>Cumulative Layout Shift (CLS):</strong> The total amount of layout shift on a page while it is loading. This is a measure of how much the content on a page moves around while it is loading.' ); ?>
                <img src="<?php echo plugin_dir_url( __DIR__ ) . 'assets/images/cls.svg' ?>"
                     alt="Cumulative Layout Shift chart">
			</p>
			<p>
				<?php _e( '<strong>First Input Delay (FID):</strong> The time it takes for a browser to respond to a user interaction, such as clicking a button or tapping on a link. This is a measure of how responsive a web page feels to users.' ); ?>
                  <img src="<?php echo plugin_dir_url( __DIR__ ) . 'assets/images/fid.svg' ?>"
                       alt="First Input Delay chart">

			</p>
			<p>
				<?php _e( 'All three of these metrics are important for providing a good user experience. A fast LCP means that users will not have to wait long for the main content of a page to load. A low CLS means that users will not have to deal with content that moves around while they are trying to read it. And a low FID means that users will be able to interact with a web page quickly and easily.' ); ?>
			</p>
		</span>
            </li>
        </ul>

		<?php
	}

	public static function add_new_widget_function() {
		$nonce_field = wp_nonce_field( 'sg_add_new_url', 'sg_add_new_nonce_field' );
		$content     = '<form name="speedguard_add_url" id="speedguard_add_url"  method="post" action="">' . $nonce_field . '
		<input class="form-control"  type="text" id="speedguard_new_url" name="speedguard_new_url" value="" placeholder="' . __( 'Start typing the title of the post, page or custom post type...', 'speedguard' ) . '" autofocus="autofocus"/>
		<input type="hidden" id="blog_id" name="blog_id" value="" />
		<input type="hidden" id="speedguard_new_url_permalink" name="speedguard_new_url_permalink" value=""/> 
		<input type="hidden" id="speedguard_item_type" name="speedguard_item_type" value=""/> 
		<input type="hidden" id="speedguard_new_url_id" name="speedguard_new_url_id" value=""/>
		<input type="hidden" name="speedguard" value="add_new_url" />
		<input type="submit" name="Submit" class="button action" value="' . __( 'Add', 'speedguard' ) . '" />
		</form>';
		echo $content;
	}

	public static function important_questions_widget_function() {
		echo SpeedGuard_Widgets::get_important_questions_widget_function();
	}

	public static function get_important_questions_widget_function() {
        //Convert this function to return instead of echo

		$links = [
			sprintf( __( '%1$sWhy CWV fail after they were passing before? [video]%2$s', 'speedguard' ), '<a href="https://www.youtube.com/watch?v=Q40B5cscObc" target="_blank">', '</a>' ),
			sprintf( __( '%1$sOne single reason why your CWV are not passing [video]%2$s', 'speedguard' ), '<a href="https://youtu.be/-d7CPbjLXwg?si=VmZ_q-9myI4SBYSD" target="_blank">', '</a>' ),
			sprintf( __( '%1$s5 popular recommendations that don’t work [video]%2$s', 'speedguard' ), '<a href="https://youtu.be/5j3OUaBDXKI?si=LSow4BWgtF9cSQKq" target="_blank">', '</a>' ),
		];
		$content = '<ul>';
		foreach ( $links as $link ) {
			$content .= '<li>' . $link . '</li>';
		}
		$content .= '</ul>';

        return $content;
	}

	public static function about_widget_function() {
		$picture        = '<a href="https://sabrinazeidan.com/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=avatar" target="_blank"><div id="szpic"></div></a>';
		$hey            = sprintf( __( 'Hey!%1$s My name is %3$sSabrina%4$s. 
		%1$sI speed up websites every day, and I built this plugin because I needed a simple tool to monitor site speed and notify me if something is not right.%2$s
		%1$sHope it will be helpful for you too.%2$s
		%2$s', 'speedguard' ), '<p>', '</p>', '<a href="https://sabrinazeidan.com/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=sabrina" target="_blank">', '</a>' );
		$rate_link      = 'https://wordpress.org/support/plugin/speedguard/reviews/?rate=5#new-post';
		$rate_it        = sprintf( __( 'If you like it, I would greatly appreciate if you add your %1$s★★★★★%2$s to spread the love.', 'speedguard' ), '<a class="rate-link" href="' . $rate_link . '" target="_blank">', '</a>' );
		$translate_link = 'https://translate.wordpress.org/projects/wp-plugins/speedguard/';
		$translate_it   = sprintf( __( 'You can also help to %1$stranslate it to your language%2$s so that more people will be able to use it ❤︎', 'speedguard' ), '<a href="' . $translate_link . '" target="_blank">', '</a>' );
		$cheers         = sprintf( __( 'Cheers!', 'speedguard' ) );
		$content        = $picture . $hey . '<p>' . $rate_it . '</p><p>' . $translate_it . '<p>' . $cheers;
		echo $content;
	}

	function speedguard_dashboard_widget_function() {
		wp_add_dashboard_widget( 'speedguard_dashboard_widget', __( 'Current Performance', 'speedguard' ), [
				$this,
				'origin_results_widget_function',
			], '', [ 'echo' => 'true' ] );
		// Widget position
		global $wp_meta_boxes;
		$normal_dashboard      = $wp_meta_boxes[ 'dashboard' . ( defined( 'SPEEDGUARD_MU_NETWORK' ) ? '-network' : '' ) ]['normal']['core'];
		$example_widget_backup = [ 'speedguard_dashboard_widget' => $normal_dashboard['speedguard_dashboard_widget'] ];
		unset( $normal_dashboard['speedguard_dashboard_widget'] );
		$sorted_dashboard                             = array_merge( $example_widget_backup, $normal_dashboard );
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}

	public function speedguard_admin_bar_widget( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( is_front_page() ) {
			$type              = 'homepage';
			$current_item_id   = '';
			$current_item_link = get_site_url(); // TODO Multisite
			// Check if it's already guarded
			$homepage_found = SpeedGuard_Tests::is_homepage_guarded();
			if ( ! empty( $homepage_found ) ) {
				$is_guarded = true;
				$test_id    = $homepage_found;
				$load_time  = get_post_meta( $test_id, 'sg_test_result' );
			} else {
				$is_guarded = false;
			}
		} elseif ( is_singular( SpeedGuard_Admin::supported_post_types() ) ) {
			global $post;
			$type              = 'single';
			$current_item_id   = $post->ID;
			$current_item_link = get_permalink( $current_item_id );
			$speedguard_on     = get_post_meta( $current_item_id, 'speedguard_on', true );
			if ( $speedguard_on && $speedguard_on[0] === 'true' ) {
				$is_guarded = true;
				$test_id    = $speedguard_on[1];
				$load_time  = get_post_meta( $test_id, 'sg_test_result' );
			} else {
				$is_guarded = false;
			}
		} elseif ( is_archive() && ! is_post_type_archive() && ! is_date() ) {
			$type              = 'archive';
			$current_item_id   = get_queried_object()->term_id;
			$current_item_link = get_term_link( $current_item_id );
			$speedguard_on     = get_term_meta( $current_item_id, 'speedguard_on', true );
			if ( $speedguard_on && $speedguard_on[0] === 'true' ) {
				$is_guarded = true;
				$test_id    = $speedguard_on[1];
				$load_time  = get_post_meta( $test_id, 'sg_test_result' );
			} else {
				$is_guarded = false;
			}
		}
		// The output
		// There is the load time
		if ( isset( $is_guarded ) && ! empty( $load_time ) && $load_time === 'waiting' ) {
			$title  = __( 'Testing...', 'speedguard' );
			$href   = '';
			$atitle = __( 'Test is running currently', 'speedguard' );
		} elseif ( isset( $is_guarded ) && $is_guarded === true ) {
			if ( ( ! empty( $load_time[0]['displayValue'] ) ) && ( ! empty( $load_time[0]['score'] ) ) ) {
				//TODO can be removed
				// $title  = '<span data-score="' . $load_time[0]['score'] . '" class="speedguard-score"><span>●</span> ' . $load_time[0]['displayValue'] . '</span>';
				//$href   = SpeedGuard_Admin::speedguard_page_url( 'tests' ) . '#speedguard-add-new-url-meta-box';
				//$atitle = __( 'This page load time', 'speedguard' );
			}
		} elseif ( isset( $is_guarded ) && $is_guarded === false ) { // Item is not guarded or test is in process currently
			$add_url_link = add_query_arg( [
					'speedguard' => 'add_new_url',
					'new_url_id' => $current_item_id,
				], SpeedGuard_Admin::speedguard_page_url( 'tests' ) );
			$title        = '<form action="' . $add_url_link . '" method="post">
				<input type="hidden" id="blog_id" name="blog_id" value="" />
				<input type="hidden" name="speedguard" value="add_new_url" /> 
				<input type="hidden" name="speedguard_new_url_id" value="' . $current_item_id . '" />	
				<input type="hidden" id="speedguard_new_url_permalink" name="speedguard_new_url_permalink" value="' . $current_item_link . '"/>
				<input type="hidden" id="speedguard_item_type" name="speedguard_item_type" value="' . $type . '"/> 
				<button style="border: 0;  background: transparent; color:inherit; cursor:pointer;">' . __( 'Test speed', 'speedguard' ) . '</button></form>';
			$href         = SpeedGuard_Admin::speedguard_page_url( 'tests' );
			$atitle       = '';
		}
		$args = [
			'id'    => 'speedguard_ab',
			'title' => isset( $title ) ? $title : '',
			'href'  => isset( $href ) ? $href : '',
			'meta'  => [
				'class'  => 'menupop',
				'title'  => isset( $atitle ) ? $atitle : '',
				'target' => 'blank',
			],
		];
		$wp_admin_bar->add_node( $args );
		// }
	}
}

new SpeedGuard_Widgets();
