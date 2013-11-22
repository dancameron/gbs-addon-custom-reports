<script type="text/javascript">

jQuery(document).ready(function($){
	jQuery('#gb_deal_summary_sales_start_date').datepicker();
	jQuery('#gb_deal_summary_sales_end_date').datepicker({maxDate: 0});
});

</script>

<div id="report_navigation clearfix">
  <?php 
  	$report = Group_Buying_Reports::get_instance( $_GET['report'] ); 
  	$start_time = ( isset( $_REQUEST['summary_sales_start_date'] ) && strtotime( $_REQUEST['summary_sales_start_date'] ) <= current_time( 'timestamp' ) ) ? strtotime( $_REQUEST['summary_sales_start_date'] ) : current_time( 'timestamp' )-604800;
  	$time = ( isset( $_REQUEST['summary_sales_end_date'] ) && strtotime( $_REQUEST['summary_sales_end_date'] ) <= current_time( 'timestamp' ) ) ? strtotime( $_REQUEST['summary_sales_end_date'] ) : current_time( 'timestamp' );
  	?>
	<form id="summary_sales_start_date" action="<?php echo $report->get_url() ?>" method="post">
			<?php gb_e('Start:') ?> <input type="text" value="<?php echo date( 'm/d/Y', $start_time ); ?>" name="summary_sales_start_date" id="gb_deal_summary_sales_start_date" />
			<?php gb_e('End:') ?> <input type="text" value="<?php echo date( 'm/d/Y', $time ); ?>" name="summary_sales_end_date" id="gb_deal_summary_sales_end_date" />
		<input type="submit" class="submit button">
	</form>
</div>
