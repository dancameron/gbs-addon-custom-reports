<?php

class Group_Buying_Reports_VE extends Group_Buying_Controller {
	const REPORT_SLUG = '...';

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
	}

	public function setup_new_template( $view ) {
		if ( $_GET['report'] == self::REPORT_SLUG ) {
			return GB_RP_PATH . '/views/report.php';	
		}
		return $view;
	}

	public function add_navigation() {
		include GB_RP_PATH . '/views/navigation.php';
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
		

		// Build Records
		$voucher_array = array();
		foreach ( $deals->posts as $deal_id ) {
			
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
		$report = Group_Buying_Reports::get_instance( '...' );
		$link = add_query_arg(array('report' => 'voucher_exp'), $report->get_url() );
		$addons['gb_simple_charities'] = array(
			'label' => self::__( 'Custom Report' ),
			'description' => sprintf( self::__( 'Generate report ...