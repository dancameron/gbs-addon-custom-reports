<?php 

function gb_get_voucher_exp_report_url( $csv = FALSE )
{
	if ( isset( $_GET['id'] )) {
		$charity_slug = $_GET['id'];
	}

	$report = Group_Buying_Reports::get_instance( 'voucher_exp' );
	if ( $csv ) {
		return apply_filters('gb_get_voucher_exp_report_url', add_query_arg(array('report' => 'voucher_exp'), $report->get_csv_url() ) );
	}
	return apply_filters('gb_get_voucher_exp_report_url', add_query_arg(array('report' => 'voucher_exp'), $report->get_url() ) );
}
	function gb_voucher_exp_report_url( $charity_slug = null )
	{
		echo apply_filters('gb_voucher_exp_report_url', gb_get_voucher_exp_report_url());
	}
	function gb_voucher_exp_report_csv_url( $charity_slug = null )
	{
		echo apply_filters('gb_voucher_exp_report_url', gb_get_voucher_exp_report_url(true) );
	}