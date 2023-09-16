<?php
/**
 *
 *   Class responsible for SpeedGuard Tests Page View
 */

// WP_List_Table is not loaded automatically
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * New table class that extends the WP_List_Table
 */
class SpeedGuard_List_Table extends WP_List_Table {
	public function no_items() {
		esc_html_e( 'No pages guarded yet. Add something in the field above for the start.', 'speedguard' );
	}


	public function prepare_items( $client_id = '' ) {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$data     = $this->table_data( $client_id );
		usort( $data, [ &$this, 'sort_data' ] );
		$perPage     = 20;
		$currentPage = $this->get_pagenum();
		$totalItems  = count( $data );
		$this->set_pagination_args(
			[
				'total_items' => $totalItems,
				'per_page'    => $perPage,
			]
		);
		$data                  = array_slice( $data, ( ( $currentPage - 1 ) * $perPage ), $perPage );
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items           = $data;
		$this->get_table_classes();
		$this->process_bulk_action();
	}

	//Set up all columns
    public function get_columns() {
		// Display Columns set based on Test type choice in Settigns
		$sg_options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		$columns = [
			'cb'                 => '<input type="checkbox" />',
			'guarded_page_title' => __( 'URL', 'speedguard' ),
		];
		// CWV
		if ( $sg_options['test_type'] === 'cwv' ) {
			// Mobile
			$columns['cwv_mobile_lcp'] = __( 'LCP', 'speedguard' );
			$columns['cwv_mobile_cls'] = __( 'CLS', 'speedguard' );
			$columns['cwv_mobile_fid'] = __( 'FID', 'speedguard' );
			// Desktop
			$columns['cwv_desktop_lcp'] = __( 'LCP', 'speedguard' );
			$columns['cwv_desktop_cls'] = __( 'CLS', 'speedguard' );
			$columns['cwv_desktop_fid'] = __( 'FID', 'speedguard' );
		} // PSI
		elseif ( $sg_options['test_type'] === 'psi' ) {
			// Mobile
			$columns['psi_mobile_lcp'] = __( 'LCP', 'speedguard' );
			$columns['psi_mobile_cls'] = __( 'CLS', 'speedguard' );
			// Desktop
			$columns['psi_desktop_lcp'] = __( 'LCP', 'speedguard' );
			$columns['psi_desktop_cls'] = __( 'CLS', 'speedguard' );
		}

		$columns['report_date'] = __( 'Updated', 'speedguard' );
        return $columns;
	}

	// Which columns are hidden
	public function get_hidden_columns() {
		return [];
	}

    //Which columns are sortable
	public function get_sortable_columns() {
		return [
			'guarded_page_title' => [ 'guarded_page_title', false ],
			'psi_mobile_lcp'     => [ 'psi_mobile_lcp', false ],
			'psi_mobile_cls'     => [ 'psi_mobile_cls', false ],
			'cwv_mobile_lcp'     => [ 'cwv_mobile_lcp', false ],
			'cwv_mobile_cls'     => [ 'cwv_mobile_cls', false ],
			'cwv_mobile_fid'     => [ 'cwv_mobile_fid', false ],
			'cwv_desktop_lcp'    => [ 'cwv_desktop_lcp', false ],
			'cwv_desktop_cls'    => [ 'cwv_desktop_cls', false ],
			'cwv_desktop_fid'    => [ 'cwv_desktop_fid', false ],
			'psi_desktop_lcp'    => [ 'psi_desktop_lcp', false ],
			'psi_desktop_cls'    => [ 'psi_desktop_cls', false ],
			'report_date'        => [ 'report_date', false ],
		];
	}

	// Data for Tests results table
	private function table_data( $client_id = '' ) {
		// Data we will return in the end
		$data = [];
		// Get all guarded pages
		$guarded_pages = get_posts( [
			'post_type'      => SpeedGuard_Admin::$cpt_name,
			'post_status'    => 'publish',
			'posts_per_page' => 100, //TODO improve this limit with ajax chunks
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );
		// If there are any guarded pages:
		if ( $guarded_pages ) :
			foreach ( $guarded_pages as $guarded_page_id ) {
				$guarded_page_url = get_post_meta( $guarded_page_id, 'speedguard_page_url', true );
				// Prepare basic columns
				$report_link = add_query_arg( [ 'url' => $guarded_page_url ], 'https://developers.google.com/speed/pagespeed/insights/' );
				$updated     = get_the_modified_date( 'Y-m-d H:i:s', $guarded_page_id );
				// Define basic data for the item
				$thisTestData = [
					'guarded_page_id'    => $guarded_page_id,
					'guarded_page_title' => '<a href="' . $guarded_page_url . '" target="_blank">' . $guarded_page_url . '</a>',
					'report_date'        => $updated . '<a href="' . $report_link . '" target="_blank">🔗</a>',
				];
				// Get test result data
				$sg_test_result = get_post_meta( $guarded_page_id, 'sg_test_result', true );
				// Start Prepare PSI data and CWV data with the loop (use SG_METRICS_ARRAY make a loop)
				foreach ( SpeedGuard_Admin::SG_METRICS_ARRAY as $device => $test_types ) {
					foreach ( $test_types as $test_type => $metrics ) {
						foreach ( $metrics as $metric ) {
							$core_value                                                 = SpeedGuardWidgets::single_metric_display( $sg_test_result, $device, $test_type, $metric );
							$thisTestData[ $test_type . '_' . $device . '_' . $metric ] = $core_value; // this is a string to display // TODO rename
						}
					}
				}
				$data[] = $thisTestData;
			} // end foreach $guarded pages
		endif; // There are $guarded_pages

		return $data;
	}

	function get_table_classes() {
        $sg_test_type = SpeedGuard_Settings::global_test_type();
		if ( 'cwv' === $sg_test_type ) {
			$test_type = 'cwv-test-type';
		} elseif ( 'psi' === $sg_test_type ) {
			$test_type = 'psi-test-type';
		}

		return [
			'wp-list-table',
			'widefat',
			'striped',
			'table-view-list',
			'toplevel_page_speedguard_tests',
			$test_type,
		];
	}

	// Columns data

	public function process_bulk_action() {
		$doaction = $this->current_action();
		if ( ! empty( $doaction ) && ! empty( $_POST['guarded-pages'] ) ) {
			foreach ( $_POST['guarded-pages'] as $guarded_page_id ) {
				if ( $doaction === 'retest_load_time' ) {
					$result = SpeedGuard_Tests::update_speedguard_test( $guarded_page_id );
				} elseif ( $doaction === 'delete' ) {
					wp_delete_post( $guarded_page_id, true ); // TODO check error
					$result = 'delete_guarded_pages';
				}
			}
		}
		if ( isset( $result ) ) {
			$redirect_to = add_query_arg( 'speedguard', $result );
			wp_safe_redirect( esc_url_raw( $redirect_to ) );
			exit;
		}
	}
	// END


	// Table data

	public function get_columnsss() {
		// Display Columns set based on Test type choice in Settigns
        $sg_test_type = SpeedGuard_Admin::global_test_type();
        // First 2 columns
		$columns   = [];
		$columns[] = [
			'cb'                 => '<
input type="checkbox" />',
			'guarded_page_title' => __( 'URL', 'speedguard' ),
		];

		// Mobile
		if ( 'cwv' === $sg_test_type ) {
			$columns[] = [
				'cwv_mobile_lcp' => __( 'LCP', 'speedguard' ),
				'cwv_mobile_cls' => __( 'CLS', 'speedguard' ),
				'cwv_mobile_fid' => __( 'FID', 'speedguard' ),
			];
		} elseif ( 'psi' === $sg_test_type ) {
			$columns[] = [
				'psi_mobile_lcp' => __( 'LCP', 'speedguard' ),
				'psi_mobile_cls' => __( 'CLS', 'speedguard' ),
			];
		}
		// Desktop
		if ( 'cwv' === $sg_test_type) {
			$columns[] = [
				'cwv_desktop_lcp' => __( 'LCP', 'speedguard' ),
				'cwv_desktop_cls' => __( 'CLS', 'speedguard' ),
				'cwv_desktop_fid' => __( 'FID', 'speedguard' ),
			];
		} elseif ( 'psi' === $sg_test_type) {
			$columns[] = [
				'psi_desktop_lcp' => __( 'LCP', 'speedguard' ),
				'psi_desktop_cls' => __( 'CLS', 'speedguard' ),
			];
		}

		// Date column
		$columns[] = [
			'report_date' => __( 'Updated', 'speedguard' ),
		];

		return $columns;
	}

	// Columns names

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="guarded-pages[]" value="%s" />', $item['guarded_page_id'] );
	}

	// Sort data the variables set in the $_GET

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'guarded_page_title':
			case 'psi_mobile_lcp':
			case 'psi_mobile_cls':
			case 'cwv_mobile_lcp':
			case 'cwv_mobile_cls':
			case 'cwv_mobile_fid':
			case 'cwv_desktop_lcp':
			case 'cwv_desktop_cls':
			case 'cwv_desktop_fid':
			case 'psi_desktop_lcp':
			case 'psi_desktop_cls':
			case 'report_date':
				// case 'load_time':// TODO remove
				if ( isset( $item[ $column_name ] ) ) { // TODO fix this after load_time
					$item[ $column_name ] = is_string( $item[ $column_name ] ) ? $item[ $column_name ] : 'error';

					return $item[ $column_name ];
				}
			default:
				return print_r( $item, true );
		}
	}

	// Edit actions

	public function get_bulk_actions() {
		$actions = [
			'delete'           => __( 'Stop guarding', 'speedguard' ),
			'retest_load_time' => __( 'Retest', 'speedguard' ),
		];

		return $actions;
	}

	private function sort_data( $a, $b ) {
		// Set defaults
		$orderby = 'guarded_page_title';
		$order   = 'asc';
		// If orderby is set, use this as the sort column
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = $_GET['orderby'];
		}
		// If order is set use this as the order
		if ( ! empty( $_GET['order'] ) ) {
			$order = $_GET['order'];
		}
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		if ( $order === 'asc' ) {
			return $result;
		}

		return - $result;
	}
}

class SpeedGuard_Tests {
	function __construct() {
		add_action( 'rest_api_init', [ $this, 'speedguard_rest_api_register_routes' ] );
		add_action( 'admin_init', [ $this, 'process_speedguard_actions' ] );
	}

	public static function process_speedguard_actions() {
		// add new test via form
		if ( ! empty( $_POST['speedguard'] ) && $_POST['speedguard'] === 'add_new_url' ) {
			$url    = ( ! empty( $_POST['speedguard_new_url_permalink'] ) ) ? $_POST['speedguard_new_url_permalink'] : $_POST['speedguard_new_url'];
			$result = self::try_add_speedguard_test( $url, $_POST['speedguard_item_type'], $_POST['speedguard_new_url_id'], $_POST['blog_id'] );
		}
	}

	// TODO: separate add_test (decide here add or update) then: update_test and create_test

	public static function try_add_speedguard_test( $url_to_add = '', $guarded_item_type = '', $guarded_item_id = '', $guarded_post_blog_id = '', $already_guarded = false ) {
		// blog id 1
		if ( empty( $url_to_add ) ) {
			$redirect_to = add_query_arg( 'speedguard', 'add_new_url_error_empty' );
		} elseif ( ! empty( $url_to_add ) ) {
			$url_to_add = strtok( trim( preg_replace( '/\t+/', '', htmlspecialchars( $url_to_add ) ) ), '?' );
			if ( ! filter_var( $url_to_add, FILTER_VALIDATE_URL ) ) {
				$redirect_to = add_query_arg( 'speedguard', 'add_new_url_error_not_url' );
			} else { // it's a valid URL. Does it belong to the current domain?
				$entered_domain = parse_url( $url_to_add );
				// If it doesn't belong to the current domain and it's not a PRO version -> redirect
				if ( ( $_SERVER['SERVER_NAME'] != $entered_domain['host'] ) && ! defined( 'SPEEDGUARD_PRO' ) ) {
					$redirect_to = add_query_arg( 'speedguard', 'add_new_url_error_not_current_domain' );
				}//$url_to_add doesn't belong to the current domain and it's not PRO
				else { // $url_to_add is a valid URL. It belongs to the current domain. Do we know the type?
					if ( empty( $guarded_item_type ) ) {// find out the type, item id and blog id //TODO
						if ( trailingslashit( $url_to_add ) === trailingslashit( get_site_url() ) ) { // homepage
							$guarded_item_type   = 'homepage';
							$is_homepage_guarded = self::is_homepage_guarded();
							$already_guarded     = ( ! empty( $is_homepage_guarded ) ) ? true : false;
							$existing_test_id    = ( ! empty( $is_homepage_guarded ) ) ? $is_homepage_guarded : false;
						} else { // single or archive
							$guarded_item_id = url_to_postid( $url_to_add );
							if ( $guarded_item_id != 0 ) {
								$guarded_item_type = 'single';
								$speedguard_on     = get_post_meta( $guarded_item_id, 'speedguard_on', true );
								$already_guarded   = ( ! empty( $speedguard_on ) && ( $speedguard_on[0] === 'true' ) ) ? true : false;
								$existing_test_id  = ( ! empty( $speedguard_on ) && ( $speedguard_on[0] === 'true' ) ) ? ( $speedguard_on[1] ) : false;
							} elseif ( $guarded_item_id === 0 ) { // it's archive. Let's find the term
								// $slug = basename($url_to_add).PHP_EOL;
								$taxonomies = get_taxonomies();
								foreach ( $taxonomies as $tax_type_key => $taxonomy ) {
									if ( $term_object = get_term_by( 'slug', basename( $url_to_add ) . PHP_EOL, $taxonomy ) ) {
										// TODO what if there are a few terms with the same slug in different taxonomies
										// TODO What if nothing is found
										$guarded_item_id   = $term_object->term_id;
										$guarded_item_type = 'archive';
										break;
									}
								}
								$speedguard_on    = get_term_meta( $guarded_item_id, 'speedguard_on', true );
								$already_guarded  = ( ! empty( $speedguard_on ) && ( $speedguard_on[0] === 'true' ) ) ? true : false;
								$existing_test_id = ( ! empty( $speedguard_on ) && ( $speedguard_on[0] === 'true' ) ) ? ( $speedguard_on[1] ) : false;
							}
						}
					}
					// we have: $url_to_add, $guarded_item_type, $guarded_item_id, $guarded_post_blog_id now + $already_guarded status

					if ( ! empty( $already_guarded ) && ( $already_guarded === true ) && empty( $redirect_to ) && ! empty( $existing_test_id ) && ( 'publish' === get_post_status( $existing_test_id ) ) ) {
						$result      = self::update_speedguard_test( $existing_test_id );
						$redirect_to = add_query_arg( 'speedguard', $result );
					} else { // Valid and not guarded yet >>> ADD
						$result = self::create_speedguard_test( $url_to_add, $guarded_item_type, $guarded_item_id );

						if ( empty( $redirect_to ) ) {
							$redirect_to = add_query_arg( 'speedguard', 'new_url_added' );
						}
					}
				}
			}
		}
		// Result: Should be set in any case
		if ( ! get_transient( 'speedguard-notice-activation' ) ) {
			wp_safe_redirect( esc_url_raw( $redirect_to ) );
			exit;
		}
	}

	/**
	 * @return bool
     * Checks if the homepage is guarded.
	 */
	public static function is_homepage_guarded() {
    $args = [
	    'post_type' => SpeedGuard_Admin::$cpt_name,
	    'post_status' => 'publish',
	    'posts_per_page' => 1,
	    'fields'         => 'ids',
	    'no_found_rows'  => true,
	    'meta_query' => [
		    [
			    'key' => 'speedguard_item_type',
			    'compare' => 'LIKE',
			    'value' => 'homepage',
		    ],
	    ],
    ];
    $posts = get_posts($args);
    return ! empty($posts);
}

	// TODO unify create and update functions
	/*
	 * Update the existing test
	 */
	public static function update_speedguard_test( $guarded_page_id ) {
		// TODO: All these if not automatically on activation
		$updated_ts         = get_post_timestamp( $guarded_page_id, 'modified' ); // no timezone
		$updated_plus_three = $updated_ts + 3 * 60;
		if ( time() > $updated_plus_three ) {
			// TODO if there are a few newer and a few old - show notice accordingly

			// Set waiting status for the tests and sidewide
			update_post_meta( $guarded_page_id, 'sg_test_result', 'waiting' );
			SpeedGuard_Admin::update_this_plugin_option( 'sg_origin_result', 'waiting' );

			// TODO: Replace with action
			$response = 'speedguard_test_being_updated'; // TODO: true, false, error
		} else {
			$response = 'slow_down';
		}

		return $response;
	}

	/*
	* Create a new test
	*/
	public static function create_speedguard_test( $url_to_add = '', $guarded_item_type = '', $guarded_item_id = '' ) {
		if ( empty( $url_to_add ) ) {
			return;
		}
		$code            = $url_to_add;
		$new_target_page = [
			'post_title'  => $code,
			'post_status' => 'publish',
			'post_type'   => SpeedGuard_Admin::$cpt_name,
		];
		if ( defined( 'SPEEDGUARD_MU_NETWORK' ) ) {
			switch_to_blog( get_network()->site_id );
		}
		$target_page_id = wp_insert_post( $new_target_page );
		if ( isset( $target_page_id ) ) {
			update_post_meta( $target_page_id, 'speedguard_page_url', $url_to_add );
			update_post_meta( $target_page_id, 'speedguard_item_type', $guarded_item_type );

			// Set waiting status for the tests and sidewide
			SpeedGuard_Admin::update_this_plugin_option( 'sg_origin_result', 'waiting' );
			update_post_meta( $target_page_id, 'sg_test_result', 'waiting' );

			// TODO always pass blog id
			if ( ! empty( $guarded_post_blog_id ) ) {
				update_post_meta( $target_page_id, 'guarded_post_blog_id', $guarded_post_blog_id );
			}
			// check url as guarded
			if ( ( $guarded_item_type === 'single' ) || ( $guarded_item_type === 'archive' ) ) {
				update_post_meta( $target_page_id, 'guarded_post_id', $guarded_item_id );
				$set_speedguard_on = ( $guarded_item_type === 'single' ) ? update_post_meta(
					$guarded_item_id,
					'speedguard_on',
					[
						'true',
						$target_page_id,
					]
				) : update_term_meta( $guarded_item_id, 'speedguard_on', [ 'true', $target_page_id ] );
			}
			if ( defined( 'SPEEDGUARD_MU_NETWORK' ) ) {
				restore_current_blog();
			}
			$response = 'new_url_added'; // TODO check thouroughly
		} else {
			$response = 'error';
		}

		return $response;
	}

	public static function tests_list_metabox() {
		$exampleListTable = new SpeedGuard_List_Table();
		echo '<form id="wpse-list-table-form" method="post">';
		$exampleListTable->prepare_items();
		$exampleListTable->display();
		echo '</form>';
	}

	public static function tests_page() {
		if ( SpeedGuard_Admin::is_screen( 'tests' ) ) {
			SpeedGuardWidgets::add_meta_boxes();
			?>
			<div class="wrap">
				<h2><?php _e( 'Speedguard :: Guarded pages', 'speedguard' ); ?></h2>
				<div id="poststuff" class="metabox-holder has-right-sidebar">
					<div id="side-info-column" class="inner-sidebar">
						<?php

						do_meta_boxes( '', 'side', 0 );
						?>
					</div>
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<?php do_meta_boxes( '', 'main-content', '' ); ?>
						</div>
					</div>
				</div>
				</form>
			</div>
			<?php
		}
	}

	function speedguard_rest_api_register_routes() {
		register_rest_route(
			'speedguard',
			'/search',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'speedguard_rest_api_search' ],
				// this part fetches the right $_GET params //For internal calls
				'permission_callback' => function ( WP_REST_Request $request ) {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	function speedguard_rest_api_search( WP_REST_Request $request ) {
		$search_term = $request->get_param( 'term' );
		if ( empty( $search_term ) ) {
			return;
		}

		// TODO PRO: WP REST API Auth search all blogs if Network Activated
		if ( defined( 'SPEEDGUARD_MU_NETWORK' ) ) {
			$sites = get_sites();
			$posts = [];
			foreach ( $sites as $site ) {
				$blog_id = $site->blog_id;
				switch_to_blog( $blog_id );
				$this_blog_posts = self::speedguard_search_function( $search_term );
				$posts           = array_merge( $posts, $this_blog_posts );
				restore_current_blog();
			}//endforeach
		}//endif network
		else {
			$posts = self::speedguard_search_function( $search_term );
		}

		return $posts;
	}

	function speedguard_search_function( $search_term ) {
		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => 'speedguard_on',
				'compare' => 'NOT EXISTS',
				'value'   => '',
			],
			[
				'key'     => 'speedguard_on',
				'compare' => '==',
				'value'   => 'false',
			],
		];

		$args = [
			'post_type'              => SpeedGuard_Admin::supported_post_types(),
			'post_status'            => 'publish',
			'posts_per_page'         => 3,
			'fields'                 => 'ids',
			's'                      => $search_term,
			'no_found_rows'          => true,
			'meta_query'             => $meta_query,
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		$this_blog_found_posts = get_posts($args);


		$temp                  = [];
		foreach ( $this_blog_found_posts as $key => $post_id ) {
			// $key = 'ID';
			$temp    = [
				'ID'        => $post_id,
				'permalink' => get_permalink( $post_id ),
				'blog_id'   => get_current_blog_id(),
				'label'     => get_the_title( $post_id ),
				'type'      => 'single',
			];
			$posts[] = $temp;
		}

		// Include Terms too
		$the_terms = get_terms(
			[
				'name__like' => $search_term,
				'hide_empty' => true,
				'meta_query' => $meta_query,
			]
		);
		if ( count( $the_terms ) > 0 ) {
			foreach ( $the_terms as $term ) {
				$temp    = [
					'ID'        => $term->term_id,
					'permalink' => get_term_link( $term ),
					'blog_id'   => get_current_blog_id(),
					'label'     => $term->name,
					'type'      => 'archive',
				];
				$posts[] = $temp;
			}
		}
		if ( ! empty( $posts ) ) {
			return $posts;
		}
	}
}

new SpeedGuard_Tests();
