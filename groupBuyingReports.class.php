<?php

class Group_Buying_Reports_VE extends Group_Buying_Controller {
	const REPORT_SLUG = 'voucher_exp';

	public static function init() {
		parent::init();

		// Create Report
		add_action( 'gb_reports_set_data', array( get_class(), 'create_report' ) );

		// Filter title
		add_filter( 'gb_reports_get_title', array( get_class(), 'filter_title' ), 10, 2 );

		// Templates
		
		if ( !version_compare( Group_Buying::GB_VERSION, '4.0.1', '>=' ) ) { // If the action doesn't exist, pre 4.0.5, add the necessary actions
			add_action( 'group_buying_template_reports/view.php', array( get_class(), 'setup_new_template' ), 100, 1 );
		
		}

		// Add the navigation
		add_action( 'gb_report_view', array( get_class(), 'add_navigation' ), 1000 );

		// Enqueue
		add_action( 'init', array( get_class(), 'enqueue' ) );
	}

	public function enqueue() {
		wp_enqueue_script( 'group-buying-admin-deal', GB_URL . '/resources/js/deal.admin.gbs.js', array( 'jquery', 'jquery-ui-draggable' ), Group_Buying::GB_VERSION );
		wp_enqueue_style( 'group-buying-admin-deal', GB_URL . '/resources/css/deal.admin.gbs.css' );
	}

	public function setup_new_template( $view ) {
		if ( $_GET['report'] == self::REPORT_SLUG ) {
			return GB_REPORT_PATH . '/views/report.php';	
		}
		return $view;
	}

	public function add_navigation() {
		include GB_REPORT_PATH . '/views/navigation.php';
	}

	public function filter_title( $title, $report ) {
		if ( $report == self::REPORT_SLUG ) {
			return self::__('Voucher Expiration Report');
		}
		return $title;
	}

	public function create_report( $report ) {
		if (
			$report->report != self::REPORT_SLUG ||
			( !isset( $_REQUEST['voucher_exp'] ) || $_REQUEST['voucher_exp'] == '' ) )
			return;

		$report->csv_available = TRUE;

		// Columns
		$columns = array(
			'voucher_exp' => self::__( 'Voucher Expiration' ),
			'name' => self::__( 'Purchaser' ),
			'email' => self::__( 'Purchaser Email' ),
			'deal_id' => self::__( 'Deal ID' ),
			'id' => self::__( 'Order #' ),
			'deal' => self::__( 'Deal' ),
			'exp' => self::__( 'Deal Exp.' ),
			'date' => self::__( 'Purchase Date' ) );
		$report->columns = $columns;
		
		// Query deals since they hold the expiration date
		$future = current_time( 'timestamp' )+( (int)$_GET['days']*86400 );
		$filter = ( isset( $_REQUEST['voucher_exp'] ) && strtotime( $_REQUEST['voucher_exp'] ) > current_time( 'timestamp' ) ) ? strtotime( $_REQUEST['voucher_exp'] ) : current_time( 'timestamp' );
		$args = array(
			'fields' => 'ids',
			'post_type' => Group_Buying_Deal::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1, // return this many
			'meta_query' => array(
				array(
					'key' => '_voucher_expiration_date',
					'value' => array( current_time( 'timestamp' ), $filter ),
					'compare' => 'BETWEEN'
				)
			)
		);
		$deals = new WP_Query( $args );
		// Build Records
		$voucher_array = array();
		foreach ( $deals->posts as $deal_id ) {
			$deal = Group_Buying_Deal::get_instance( $deal_id );

			// Get vouchers from deal
			$vouchers = Group_Buying_Voucher::get_vouchers_for_deal( $deal_id );
			foreach ( $vouchers as $voucher_id ) {
				$voucher = Group_Buying_Voucher::get_instance( $voucher_id );

				// Record
				if ( is_a( $voucher, 'Group_Buying_Voucher' ) ) {
					$purchase_id = $voucher->get_purchase_id();
					$account_id = $voucher->get_account();
					$account = Group_Buying_Account::get_instance_by_id( $account_id );
					if ( is_a( $account, 'Group_Buying_Account' ) ) {
						$user = $account->get_user();
						$voucher_array[] = array(
							'voucher_exp' => date( 'F j\, Y', $voucher->get_expiration_date() ),
							'name' => $account->get_name(),
							'email' => $user->data->user_email,
							'id' => $purchase_id,
							'date' => date( 'F j\, Y H:i:s', get_the_time( 'U', $purchase_id ) ),
							'deal' => $deal->get_title(),
							'exp' => date( 'F j\, Y H:i:s', $deal->get_expiration_date() ),
							'deal_id' => $deal->get_id(),
						);
					}

				}
			}
		}

		
		$report->records = $voucher_array;
	}

}

// Initiate the add-on
class Group_Buying_Reports_VE_Addon extends Group_Buying_Controller {

	public static function init() {
		// Hook this plugin into the GBS add-ons controller
		add_filter( 'gb_addons', array( get_class(), 'gb_add_on' ), 10, 1 );
	}

	public static function gb_add_on( $addons ) {
		if ( self::using_permalinks() ) {
			$link = add_query_arg( array( 'report' => 'voucher_exp' ), home_url( trailingslashit( get_option( Group_Buying_Reports::REPORTS_PATH_OPTION ) ) ) );
		} else {
			$link = add_query_arg( array( Group_Buying_Reports::REPORT_QUERY_VAR => 1, 'report' => 'voucher_exp'  ), home_url() );
		}
		$addons['gb_voucher_reporting'] = array(
			'label' => self::__( 'Custom Report' ),
			'description' => sprintf( self::__( 'Generate report of vouchers based on expiration. <a href="%s" class="button">Report</a>' ), $link ),
			'files' => array(
				__FILE__,
				dirname( __FILE__ ) . '/library/template-tags.php',
			),
			'callbacks' => array(
				array( 'Group_Buying_Reports_VE', 'init' ),
			),
		);
		return $addons;
	}

}
