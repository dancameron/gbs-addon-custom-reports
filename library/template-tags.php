<?php 

function gb_get_sales_summary_report_url( $csv = FALSE )
{
	if ( isset( $_GET['id'] )) {
		$charity_slug = $_GET['id'];
	}

	$report = SEC_Reports::get_instance( 'sales_summary' );
	if ( $csv ) {
		return apply_filters('gb_get_sales_summary_report_url', add_query_arg(array('report' => 'sales_summary'), $report->get_csv_url() ) );
	}
	return apply_filters('gb_get_sales_summary_report_url', add_query_arg(array('report' => 'sales_summary'), $report->get_url() ) );
}
	function gb_sales_summary_report_url( $charity_slug = null )
	{
		echo apply_filters('gb_sales_summary_report_url', gb_get_sales_summary_report_url());
	}
	function gb_sales_summary_report_csv_url( $charity_slug = null )
	{
		echo apply_filters('gb_sales_summary_report_url', gb_get_sales_summary_report_url(true) );
	}