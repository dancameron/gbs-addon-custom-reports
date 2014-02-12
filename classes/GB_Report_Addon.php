<?php

/**
 * Load via GBS Add-On API
 */
class GB_Report_Addon extends Group_Buying_Controller {
	
	public static function init() {
		require_once('GB_Reports_SS.php');
		require_once('GB_Report_Email.php');
		require_once( GB_REPORT_PATH . '/library/template-tags.php');
		
		GB_Reports_SS::init();
		GB_Report_Email::init();
	}

	public static function gb_addon( $addons ) {
		if ( self::using_permalinks() ) {
			$link = add_query_arg( array( 'report' => 'sales_summary' ), home_url( trailingslashit( get_option( Group_Buying_Reports::REPORTS_PATH_OPTION ) ) ) );
		} else {
			$link = add_query_arg( array( Group_Buying_Reports::REPORT_QUERY_VAR => 1, 'report' => 'sales_summary'  ), home_url() );
		}
		$addons['gb_sales_summary_reporting'] = array(
			'label' => self::__( 'Sales Summary Report' ),
			'description' => sprintf( self::__( 'Generate sales summary. <a href="%s" class="button">Report</a>' ), $link ),
			'files' => array(),
			'callbacks' => array(
				array( __CLASS__, 'init' ),
			),
			'active' => TRUE,
		);
		return $addons;
	}

}