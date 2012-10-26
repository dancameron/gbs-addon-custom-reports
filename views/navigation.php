<style type="text/css" media="screen">
	
</style>
<div id="report_navigation clearfix">
  <?php $report = Group_Buying_Reports::get_instance( $_GET['report'] ); ?>
	<form id="voucher_exp" action="<?php echo $report->get_url() ?>" method="post">
		<input type="text" value="<?php echo date( 'm/d/Y G:i', $_REQUEST['voucher_exp'] ); ?>" name="deal" id="deal_expiration" />
		<input type="submit">
	</form>
</div>
