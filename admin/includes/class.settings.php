<?php

/**
 *
 *   Class responsible for the SpeedGuard settings
 */
class SpeedGuard_Settings {
	static $settings_page_hook = 'speedguard_page_speedguard_settings';
	public static $speedguard_options = 'speedguard_options';

	function __construct() {
		// Register Settings sections
		add_action( 'admin_init', [ $this, 'speedguard_settings' ] );

		// This is Single Install or Multisite PER SITE
		add_action( 'added_option', [ $this, 'default_options_added' ], 10, 2 );
		add_action( 'updated_option', [ $this, 'speedguard_options_updated' ], 10, 3 );
		add_action( 'pre_update_option_speedguard_options', [ $this, 'default_options_set' ], 10, 2 );

		// For NETWORK ACTIVATED only

		add_action( 'add_site_option', [ $this, 'default_options_added' ], 10, 2 );
		add_action( 'update_site_option', [ $this, 'default_options_added' ], 10, 2 );
		// Set default plugin settings
		add_action( 'pre_update_site_option_speedguard_options', [ $this, 'default_options_set' ], 10, 2 );
		// Update options action function for Multisite
		add_action( 'network_admin_edit_speedguard_update_settings', [ $this, 'speedguard_update_settings' ] );

		add_filter( 'cron_schedules', [ $this, 'speedguard_cron_schedules' ] );
		// send report when load_time is updated by cron automatically
		add_action( 'speedguard_update_results', [ $this, 'update_results_cron_function' ] );
		add_action( 'speedguard_email_test_results', [ $this, 'email_test_results_function' ] );
	}

	public static function global_test_type() {
		$speedguard_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		if ( ! empty( $speedguard_options['test_type'] ) ) {
			return $speedguard_options['test_type'];
		} else {
			return 'cwv';
		}
	}

	public static function settings_page_function() {
		if ( SpeedGuard_Admin::is_screen( 'settings' ) ) {
			SpeedGuard_Widgets::add_meta_boxes();
			?>
            <div class="wrap">
                <h2><?php _e( 'SpeedGuard :: Settings', 'speedguard' ); ?></h2>
                <div id="poststuff" class="metabox-holder has-right-sidebar">
                    <div id="side-info-column" class="inner-sidebar">
						<?php do_meta_boxes( '', 'side', 0 ); ?>
                    </div>
                    <div id="post-body" class="has-sidebar">
                        <div id="post-body-content" class="has-sidebar-content">
                            <form method="post"
                                  action="<?php print_r( defined( 'SPEEDGUARD_MU_NETWORK' ) ? 'edit.php?action=speedguard_update_settings' : 'options.php' ); ?>">
								<?php do_meta_boxes( '', 'normal', 0 ); ?>
                            </form>


                        </div>
                    </div>
                </div>
                </form>
            </div>
			<?php
		}
	}

	public static function settings_meta_box() {
		settings_fields( 'speedguard' );
		do_settings_sections( 'speedguard' );
		submit_button( __( 'Save Settings', 'speedguard' ), 'primary', 'submit', false );
	}

	function default_options_added( $option, $new_value ) {
		$speedguard_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		if ( empty( $speedguard_options ) ) {
			// TODO set options on activation
			// if just activated + if options are not set yet
			$new_value = $this->default_options_set( [] );
			SpeedGuard_Admin::update_this_plugin_option( 'speedguard_options', $new_value );
		} elseif ( ! empty( $speedguard_options ) && $option === 'speedguard_options' ) { // if updating options
			$speedguard_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
			$admin_email        = $speedguard_options['email_me_at'];
			wp_clear_scheduled_hook( 'speedguard_update_results' );
			if ( ! wp_next_scheduled( 'speedguard_update_results' ) ) {
				wp_schedule_event( time(), 'speedguard_interval', 'speedguard_update_results' );
			}
		}
	}

	function default_options_set( $new_value = '', $old_value = '' ) {
		$admin_email = SpeedGuard_Admin::get_this_plugin_option( 'admin_email' );
		if ( empty( $new_value['show_dashboard_widget'] ) ) {
			$new_value['show_dashboard_widget'] = 'on';
		}
		if ( empty( $new_value['show_ab_widget'] ) ) {
			$new_value['show_ab_widget'] = 'on';
		}
		if ( empty( $new_value['email_me_at'] ) ) {
			$new_value['email_me_at'] = $admin_email;
		}
		if ( empty( $new_value['email_me_case'] ) ) {
			$new_value['email_me_case'] = 'current state';
		}
		if ( empty( $new_value['test_type'] ) ) {
			$new_value['test_type'] = 'cwv';
		}

		return $new_value;
	}

	function speedguard_options_updated( $option, $old_value, $value ) {
		if ( $option === 'speedguard_options' ) {
			$speedguard_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
			$admin_email        = $speedguard_options['email_me_at'];
			wp_clear_scheduled_hook( 'speedguard_update_results' );
			wp_clear_scheduled_hook( 'speedguard_email_test_results' );
			if ( ! wp_next_scheduled( 'speedguard_update_results' ) ) {
				wp_schedule_event( time(), 'speedguard_interval', 'speedguard_update_results' );
			}
		}
	}


	function update_results_cron_function() {
		// If send report is on: schedule cron job
		$speedguard_options = get_option( 'speedguard_options' );
		$email_me_case      = $speedguard_options['email_me_case'];
		if ( $email_me_case != 'never' ) {
			if ( ! wp_next_scheduled( 'speedguard_email_test_results' ) ) {
				// In 2 minutes
				wp_schedule_single_event( time() + 2 * 60, 'speedguard_email_test_results' );
			}
		}

		// Get all guarded pages
		$args          = [
			'post_type'      => SpeedGuard_Admin::$cpt_name,
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		];
		$guarded_pages = get_posts( $args );

		// Update the test results for each guarded page
		foreach ( $guarded_pages as $guarded_page_id ) {
			SpeedGuard_Tests::update_test_fn( $guarded_page_id );
		}
	}

	function email_test_results_function() {
		$speedguard_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		$email_me_case      = $speedguard_options['email_me_case'];
		if ( $email_me_case !== 'never' ) {
			SpeedGuard_Notifications::test_results_email( 'regular' );
		}
	}

	function speedguard_cron_schedules( $schedules ) {
		$check_recurrence = 1; // Check every day
		$value            = constant( 'DAY_IN_SECONDS' );
		//$value                            = 1200; //every 10 mins for testing
		$interval                         = (int) $check_recurrence * $value;
		$schedules['speedguard_interval'] = [
			'interval' => $interval, // user input integer in second
			'display'  => __( 'SpeedGuard check interval', 'speedguard' ),
		];

		return $schedules;
	}

	function show_dashboard_widget_fn( $args ) {
		$options    = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		$field_name = esc_attr( $args['label_for'] );
		if ( $options[ $field_name ] === 'on' ) {
			$checked = ' checked="checked" ';
		} else {
			$checked = '';
		}
		echo "<input type='hidden' name='speedguard_options[" . $field_name . "]' value='off' /><input " . $checked . " id='speedguard_options[" . $field_name . "]' name='speedguard_options[" . $field_name . "]' type='checkbox' />";
	}

	function show_ab_widget_fn( $args ) {
		$options    = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		$field_name = esc_attr( $args['label_for'] );
		if ( $options[ $field_name ] === 'on' ) {
			$checked = ' checked="checked" ';
		} else {
			$checked = '';
		}
		echo "<input type='hidden' name='speedguard_options[" . $field_name . "]' value='off' /><input " . $checked . " id='speedguard_options[" . $field_name . "]' name='speedguard_options[" . $field_name . "]' type='checkbox' />";
	}

	function email_me_at_fn( $args ) {
		$options    = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		$field_name = esc_attr( $args['label_for'] );
		echo "<input id='speedguard_options[" . $field_name . "]' name='speedguard_options[" . $field_name . "]' type='text' size='40' value='" . $options[ $field_name ] . "'/>";
	}

	function print_description( $item ) {
		echo $item;
	}

	function email_me_case_fn( $args ) {
		$options    = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		$field_name = esc_attr( $args['label_for'] );
		$items      = [
			'current state'           => __( 'Every day with the current state', 'speedguard' ),
			'if any URL is not GOOD'  => __( 'if any URL is not passing CWV (after daily check)', 'speedguard' ),
			'if CWV origing not GOOD' => __( 'only if CWV for the whole site (origin) is not passing CWV (after daily check)', 'speedguard' ),
			'never'                   => __( 'never', 'speedguard' ),
		];

		foreach ( $items as $item => $item_label ) {
			$checked = ( $options[ $field_name ] === $item ) ? ' checked="checked" ' : '';

			echo '<input ' . $checked . " type='radio' name='speedguard_options[" . $field_name . "]' id='" . $item . "' value='" . $item . "' /><label for='" . $item . "'>" . $item_label . '</label></br>';
		}
	}


	function test_type_fn( $args ) {
		$options    = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		$field_name = esc_attr( $args['label_for'] );
		$items      = [
			'cwv' => __( 'Core Web Vitals', 'speedguard' ),
			'psi' => __( 'PageSpeed Insights', 'speedguard' ),
		];
		echo "<select id='speedguard_options[" . $field_name . "]' name='speedguard_options[" . $field_name . "]' >";
		foreach ( $items as $item => $item_label ) {
			$selected = ( $options[ $field_name ] === $item ) ? ' selected="selected" ' : '';
			echo '<option ' . $selected . " value='$item'>$item_label</option>";
		}
		echo '</select>';
	}

	function speedguard_update_settings() {
		check_admin_referer( 'speedguard-options' );
		global $new_whitelist_options;
		$options = $new_whitelist_options['speedguard'];
		foreach ( $options as $option ) {
			if ( isset( $_POST[ $option ] ) ) {
				update_site_option( $option, $_POST[ $option ] );
			}
		}
		wp_redirect(
			add_query_arg(
				[
					'page'             => 'speedguard_settings',
					'settings-updated' => 'true',
				],
				network_admin_url( 'admin.php' )
			)
		);
		exit;
	}

	function speedguard_settings() {
		// General Settings
		register_setting( 'speedguard', 'speedguard_options' );
		add_settings_section( 'speedguard_widget_settings_section', '', '', 'speedguard' );
		add_settings_field(
			'speedguard_options',
			__( 'Show site average load time on Dashboard', 'speedguard' ),
			[
				$this,
				'show_dashboard_widget_fn',
			],
			'speedguard',
			'speedguard_widget_settings_section',
			[ 'label_for' => 'show_dashboard_widget' ]
		);

		add_settings_field(
			'speedguard_ab_widget',
			__( 'Show current page load time in Admin Bar', 'speedguard' ),
			[
				$this,
				'show_ab_widget_fn',
			],
			'speedguard',
			'speedguard_widget_settings_section',
			[ 'label_for' => 'show_ab_widget' ]
		);
		add_settings_section( 'speedguard_reports_section', '', '', 'speedguard' );
		add_settings_field(
			'speedguard_email_me_at',
			__( 'Send me report at', 'speedguard' ),
			[
				$this,
				'email_me_at_fn',
			],
			'speedguard',
			'speedguard_reports_section',
			[ 'label_for' => 'email_me_at' ]
		);
		add_settings_field(
			'speedguard_email_me_case',
			'',
			[
				$this,
				'email_me_case_fn',
			],
			'speedguard',
			'speedguard_reports_section',
			[ 'label_for' => 'email_me_case' ]
		);
		add_settings_field(
			'speedguard_test_type',
			__( 'Test type', 'speedguard' ),
			[
				$this,
				'test_type_fn',
			],
			'speedguard',
			'speedguard_reports_section',
			[ 'label_for' => 'test_type' ]
		);
	}

	function speedguard_settings_general() {
	}
}

new SpeedGuard_Settings();
