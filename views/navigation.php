<div id="report_navigation clearfix">
  <?php 
  	$report = Group_Buying_Reports::get_instance( $_GET['report'] ); 
  	$time = ( isset( $_REQUEST['voucher_exp'] ) && strtotime( $_REQUEST['voucher_exp'] ) > current_time( 'timestamp' ) ) ? strtotime( $_REQUEST['voucher_exp'] ) : current_time( 'timestamp' );
  	?>
	<form id="voucher_exp" action="<?php echo $report->get_url() ?>" method="post">
			<?php gb_e('Show vouchers expiring between now and:') ?> <input type="text" value="<?php echo date( 'm/d/Y', $time ); ?>" name="voucher_exp" id="gb_deal_voucher_expiration" />
		<input type="submit" class="submit button">
	</form>
</div>
