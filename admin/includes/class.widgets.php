<?php

/**
 *
 *   Class responsible for adding metaboxes
 */
class SpeedGuardWidgets {
	public function __construct() {
		$options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		if ( ! empty( $options ) ) {
			if ( $options['show_dashboard_widget'] === 'on' ) {
				add_action(
					'wp_' . ( defined( 'SPEEDGUARD_MU_NETWORK' ) ? 'network_' : '' ) . 'dashboard_setup',
					[
						$this,
						'speedguard_dashboard_widget',
					]
				);
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
		add_meta_box(
			'settings-meta-box',
			__( 'SpeedGuard Settings', 'speedguard' ),
			[
				'SpeedGuard_Settings',
				'settings_meta_box',
			],
			'',
			'normal',
			'core'
		);
		if ( 'cwv' === $sg_test_type ) {
			$origin_widget_title = 'Core Web Vitals (real users experience) for the entire website';
		} elseif ( 'psi' === $sg_test_type ) {
			$origin_widget_title = 'PageSpeed Insights (lab tests)';
		}

		add_meta_box(
			'speedguard-cwv-sidewide-meta-box',
			__( $origin_widget_title, 'speedguard' ),
			[
				'SpeedGuardWidgets',
				'speedguard_origin_results_meta_box',
			],
			'',
			'main-content',
			'core'
		);
		add_meta_box(
			'speedguard-speedresults-meta-box',
			__( 'PageSpeed Insights (lab tests)', 'speedguard' ),
			[
				'SpeedGuardWidgets',
				'speedguard_dashboard_widget_function',
			],
			'',
			'main-content',
			'core'
		);
		add_meta_box(
			'speedguard-add-new-url-meta-box',
			__( 'Add new URL to monitoring', 'speedguard' ),
			[
				'SpeedGuardWidgets',
				'add_new_url_meta_box',
			],
			'',
			'main-content',
			'core'
		);

		$sg_test_type = SpeedGuard_Settings::global_test_type();
		if ( 'cwv' === $sg_test_type ) {
			$test_type = ' -- Core Web Vitals';
		} elseif ( 'psi' === $sg_test_type ) {
			$test_type = ' -- PageSpeed Insights';
		}
		add_meta_box(
			'tests-list-meta-box',
			sprintf( __( 'Test results for specific URLs %s', 'speedguard' ), $test_type ),
			[
				'SpeedGuard_Tests',
				'tests_list_metabox',
			],
			'',
			'main-content',
			'core'
		);
		add_meta_box(
			'speedguard-legend-meta-box',
			__( 'How to understand the information above?', 'speedguard' ),
			[
				'SpeedGuardWidgets',
				'speedguard_legend_meta_box',
			],
			'',
			'main-content',
			'core'
		);
		add_meta_box(
			'speedguard-important-questions-meta-box',
			__( 'Important questions:', 'speedguard' ),
			[
				'SpeedGuardWidgets',
				'important_questions_meta_box',
			],
			'',
			'side',
			'core'
		);
		add_meta_box(
			'speedguard-about-meta-box',
			__( 'Do you like this plugin?', 'speedguard' ),
			[
				'SpeedGuardWidgets',
				'about_meta_box',
			],
			'',
			'side',
			'core'
		);
	}
	/**
	 * Function responsible for formatting CWV data for display
	 *
	 * @param $post
	 * @param $args
	 */
	public static function single_metric_display( $results_array, $device, $test_type, $metric ) {
		$display_value = '';
		$category      = '';
		$class         = '';
		if ( $results_array === 'waiting' ) {  // tests are currently running
			$class         = 'waiting';
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
			} else { // No data available for the metric
				$class         = 'na';
				$display_value = 'N/A';
			}
		}

		$category             = 'data-score-category="' . $category . '"';
		$class                = 'class="speedguard-' . $class . '"';
		$metric_display_value = '<span ' . $category . ' ' . $class . '>' . $display_value . '</span>';

		return $metric_display_value;
	}

	/**
	 * Tests Page -> Info Metabox output
	 */
	/**
	 * Function responsible for displaying widget with CWV side-wide results widget on Tests page
	 *
	 * @param $post
	 * @param $args
	 *
	 * @return void
	 */
	public static function speedguard_origin_results_meta_box() {
		// Retrieving data to display
		$speedguard_cwv_origin = SpeedGuard_Admin::get_this_plugin_option( 'sg_origin_results' );

		// Preparing data to display
		// TODO make this constant
		$sg_test_type = SpeedGuard_Settings::global_test_type();
		foreach ( SpeedGuard_Admin::SG_METRICS_ARRAY as $device => $test_types ) {
			foreach ( $test_types as $test_type => $metrics ) {

				if ($test_type === $sg_test_type) { //prepare metrics only for needed test type

					foreach ( $metrics as $metric ) {

						$current_metric = $device . '_' . $metric;
						$$current_metric = SpeedGuardWidgets::single_metric_display(  $speedguard_cwv_origin, $device, $test_type, $metric );

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


		$content = "
	<table class='widefat fixed striped toplevel_page_speedguard_tests_cwv_widget'>
	<thead>
	<tr class='bc-platforms'><td></td>
	<th><i class='sg-device-column mobile' aria-hidden='true' title='Mobile'></i></th>
	<th><i class='sg-device-column desktop' aria-hidden='true' title='Desktop''></i></th>
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
	'.$fid_tr.'
	</thbody>
	</table>
	<div><br>
	'
		           // TODO link to the video
		           . sprintf( __( 'N/A means that there is no data from Google available -- most likely your website have not got enough traffic for Google to make evaluation (Not enough usage data in the last 90 days for this device type)', 'speedguard' ), '<a href="#">', '</a>' ) . '</div>';

		echo $content;
	}

	public static function speedguard_legend_meta_box() {
		// Set the variable for the Core Web Vitals link.
		$cwv_link = 'https://web.dev/lcp/';

		// Create the table.
		$content = '<table>
  <tr>
    <td>
      <p>' . sprintf(
			__( 'We all know that site\'s loading speed was impacting Google ranking for quite a while now. But recently (late May 2020) company has revealed more details about %1$sCore Web Vitals%2$s — metrics that Google will be using to rank websites.', 'speedguard' ),
			'<a href="' . $cwv_link . '" target="_blank">',
			'</a>'
		) . '</p>
      <p>' . sprintf(
			__( '%1$sLargest Contentful Paint%2$s is one of them. It measures how quickly the page\'s "main content" loads — the bulk of the text or image (within the viewport, so before the user scrolls). ', 'speedguard' ),
			'<strong>',
			'</strong>'
		) . '</p>
      <p>
        ' . __( 'The intention of these changes is to improve how users perceive the experience of interacting with a web page.', 'speedguard' ) . '
      </p>
    </td>
  </tr>
  <tr>
    <td>
      <img src="' . plugin_dir_url( __DIR__ ) . 'assets/images/lcp.svg" alt="Largest Contentful Paint chart">
    </td>
  </tr>
</table>';

		// Echo the content.
		echo $content;
	}

	public static function add_new_url_meta_box() {
		$content = '<form name="speedguard_add_url" id="speedguard_add_url"  method="post" action="">   
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

	public static function important_questions_meta_box() {
		$links = [
			sprintf( __( '%1$sHow fast should a website load?%2$s', 'speedguard' ), '<a href="https://sabrinazeidan.com/how-fast-should-my-website-load/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">', '</a>' ),
			sprintf( __( '%1$sHow to serve scaled images to speed up your site?%2$s', 'speedguard' ), '<a href="https://sabrinazeidan.com/serve-scaled-images-wordpress/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">', '</a>' ),
			sprintf( __( '%1$sHow to speed up YouTube videos on your site?%2$s', 'speedguard' ), '<a href="https://sabrinazeidan.com/embed-youtube-video-wordpress-without-slowing/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">', '</a>' ),
			sprintf( __( '%1$s5 popular recommendations that don’t work%2$s', 'speedguard' ), '<a href="https://sabrinazeidan.com/how-to-speed-up-wordpress-this-dont-work/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">', '</a>' ),
		];
		echo '<ul>';
		foreach ( $links as $link ) {
			echo '<li>' . $link . '</li>';
		}
		echo '</ul>';
	}

	public static function about_meta_box() {
		$speedguard_cwv_origin = SpeedGuard_Admin::get_this_plugin_option( 'sg_origin_results' );
		$picture = '<a href="https://sabrinazeidan.com/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=avatar" target="_blank"><div id="szpic"></div></a>';
		$hey     = sprintf(
			__(
				'Hey!%1$s My name is %3$sSabrina%4$s. 
		%1$sI speed up websites everyday, and I built this plugin because I needed a simple tool to monitor site speed and notify me if something is not right.%2$s
		%1$sHope it will be helpful for you too.%2$s
		%2$s',
				'speedguard'
			),
			'<p>',
			'</p>',
			'<a href="https://sabrinazeidan.com/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=sabrina" target="_blank">',
			'</a>'
		);

		$rate_link      = 'https://wordpress.org/support/plugin/speedguard/reviews/?rate=5#new-post';
		$rate_it        = sprintf( __( 'If you like it, I would greatly appreciate if you add your %1$s★★★★★%2$s to spread the love.', 'speedguard' ), '<a class="rate-link" href="' . $rate_link . '" target="_blank">', '</a>' );
		$translate_link = 'https://translate.wordpress.org/projects/wp-plugins/speedguard/';
		$translate_it   = sprintf( __( 'You can also help to %1$stranslate it to your language%2$s so that more people will be able to use it ❤︎', 'speedguard' ), '<a href="' . $translate_link . '" target="_blank">', '</a>' );

		$cheers = sprintf( __( 'Cheers!', 'speedguard' ) );

		$content = $picture . $hey . '<p>' . $rate_it . '</p><p>' . $translate_it . '<p>' . $cheers;
		echo $content;
	}

	function speedguard_dashboard_widget() {
		wp_add_dashboard_widget(
			'speedguard_dashboard_widget',
			__( 'Site Speed Results [Speedguard]', 'speedguard' ),
			[
				$this,
				'speedguard_origin_results_meta_box',
			],
			'',
			[ 'echo' => 'true' ]
		);
		// Widget position
		global $wp_meta_boxes;
		$normal_dashboard      = $wp_meta_boxes[ 'dashboard' . ( defined( 'SPEEDGUARD_MU_NETWORK' ) ? '-network' : '' ) ]['normal']['core'];
		$example_widget_backup = [ 'speedguard_dashboard_widget' => $normal_dashboard['speedguard_dashboard_widget'] ];
		unset( $normal_dashboard['speedguard_dashboard_widget'] );
		$sorted_dashboard                             = array_merge( $example_widget_backup, $normal_dashboard );
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}

	private function speedguard_admin_bar_widget( $wp_admin_bar ) {
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
				$title  = '<span data-score="' . $load_time[0]['score'] . '" class="speedguard-score"><span>●</span> ' . $load_time[0]['displayValue'] . '</span>';
				$href   = SpeedGuard_Admin::speedguard_page_url( 'tests' ) . '#speedguard-add-new-url-meta-box';
				$atitle = __( 'This page load time', 'speedguard' );
			}
		} elseif ( isset( $is_guarded ) && $is_guarded === false ) { // Item is not guarded or test is in process currently
			$add_url_link = add_query_arg(
				[
					'speedguard' => 'add_new_url',
					'new_url_id' => $current_item_id,
				],
				SpeedGuard_Admin::speedguard_page_url( 'tests' )
			);
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

new SpeedGuardWidgets();
