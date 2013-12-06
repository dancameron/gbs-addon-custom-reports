<?php

class Group_Buying_Reports_SS extends Group_Buying_Controller {
	const REPORT_SLUG = 'sales_summary';

	public static function init() {
		parent::init();

		// Create Report
		add_action( 'gb_reports_set_data', array( get_class(), 'create_report' ) );

		// Filter title
		add_filter( 'gb_reports_get_title', array( get_class(), 'filter_title' ), 10, 2 );

		// Add the navigation
		add_action( 'gb_report_view', array( get_class(), 'add_navigation' ), 1000 );

		// Enqueue
		add_action( 'init', array( get_class(), 'enqueue' ) );
	}

	public function enqueue() {
		// Timepicker
		wp_enqueue_script( 'gb_timepicker' );
		wp_enqueue_style( 'gb_frontend_jquery_ui_style' );
	}

	public function add_navigation() {
		if ( isset( $_GET['report'] ) && $_GET['report'] == self::REPORT_SLUG ) {
			include GB_REPORT_PATH . '/views/navigation.php';
		}
	}

	public function filter_title( $title, $report ) {
		if ( $report == self::REPORT_SLUG ) {
			return self::__('Sales Summary');
		}
		return $title;
	}

	public function create_report( $report = TRUE ) {

		if ( $report->report != self::REPORT_SLUG )
			return;

		$report->csv_available = TRUE;

		// Paginations
		global $gb_report_pages;
		// Pagination variable
		$showpage = ( isset( $_GET['showpage'] ) ) ? (int)$_GET['showpage']+1 : 1 ;

		/**
		 * Setup Columns
		 * @var array
		 */
		$columns = array(
			'deal_id' => self::__( 'ID' ),
			'deal_name' => self::__( 'Deal Name' ),
			'price' => self::__( 'Price' ),
			'qty' => self::__( 'Quantity Sold' ),
			'credits' => self::__( 'Credits Used' ),
			'earn' => self::__( 'Earn' )
		);
		$report->columns = apply_filters( 'set_sales_summary_report_data_column', $columns );

		// Filter variable
		$filter = ( isset( $_GET['filter'] ) && in_array( $_GET['filter'], array( 'any', 'publish', 'draft', 'private', 'trash' ) ) ) ? $_GET['filter'] : 'publish';

		// Build an array of merchant's Deals.
		$merchants_deal_ids = array();
		$account_merchant_id = gb_account_merchant_id();
		if ( $account_merchant_id ) {
			$merchants_deal_ids = gb_get_merchants_deal_ids( $account_merchant_id );
		}
		$gb_report_pages = count( $merchants_deal_ids )/10; // set the global for later pagination

		$start_time = ( isset( $_REQUEST['summary_sales_start_date'] ) && strtotime( $_REQUEST['summary_sales_start_date'] ) <= current_time( 'timestamp' ) ) ? $_REQUEST['summary_sales_start_date'] : date( 'm/d/Y', current_time( 'timestamp' )-604800 );
  		$time = ( isset( $_REQUEST['summary_sales_end_date'] ) && strtotime( $_REQUEST['summary_sales_end_date'] ) <= current_time( 'timestamp' ) ) ? $_REQUEST['summary_sales_end_date'] : date( 'm/d/Y', current_time( 'timestamp' ) );

		$credit_payment_methods = apply_filters( 'set_credit_purchases_methods', array( Group_Buying_Affiliate_Credit_Payments::PAYMENT_METHOD, Group_Buying_Account_Balance_Payments::PAYMENT_METHOD ) );

		$purchase_array = array(); // records array
		if ( $merchants_deal_ids ) {
			// loop through all the deals
			foreach ( $merchants_deal_ids as $deal_id ) {
				$deal = Group_Buying_Deal::get_instance( $deal_id );
				$deal_title = $deal->get_title();

				$args=array(
					'post_type' => Group_Buying_Purchase::POST_TYPE,
					'posts_per_page' => -1,
					'fields' => 'ids',
					'date_query' => array(
							array(
								'after'     => $start_time,
								'before'    => date( 'm/d/Y', strtotime( $time )+86400 ), // Add a day since it will nto count the date selected otherwise.
								'inclusive' => true,
							),
						),
					'meta_query' => array(
							array(
								'key'     => '_deal_id',
								'value'    => $deal_id,
							),
						),
				);

				$purchases = new WP_Query( $args );
				
				// Loop through all the purchases to build variables, with a key of the price.
				$prices = array();
				foreach ( $purchases->posts as $purchase_id ) {
					$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
					$purchase_items = $purchase->get_products();
					foreach ( $purchase_items as $item_data => $purchase_data ) {
						/*/
							Example: 
							(
							    [0] => Array
							        (
							            [deal_id] => 499
							            [quantity] => 5
							            [data] => Array
							                (
							                )

							            [price] => 200
							            [unit_price] => 40
							            [payment_method] => Array
							                (
							                    [Account Credit (Affiliate)] => 200
							                )

							        )

							)
						/**/
						// Info only useful if the data is associated with the deal_id
						if ( $deal_id == $purchase_data['deal_id'] ) {
							// Make sure an array is set for this price
							if ( !array_key_exists( $purchase_data['unit_price'], $prices ) ) {
								$prices[$purchase_data['unit_price']] = array();
							}

							// Set the quantity purchased
							$prices[$purchase_data['unit_price']]['qty'] += $purchase_data['quantity'];

							// Credits
							if ( !isset( $prices[$purchase_data['unit_price']]['credits'] ) ) {
								$prices[$purchase_data['unit_price']]['credits'] = 0;
							}

							// Loop through each payment method to calculate credits used.
							foreach ( $purchase_data['payment_method'] as $payment_method => $total ) {
								if ( in_array( $payment_method, $credit_payment_methods ) ) {
									$prices[$purchase_data['unit_price']]['credits'] += $total;
								}
							}
						}
					}
				}

				/**
				 * Setup a parent record if the deal has had multiple prices.
				 */
				$multiple = ( count( $prices ) > 1 ) ? TRUE : FALSE ;
				if ( $multiple ) {
					$total_qty = 0;
					$total_credits = 0;
					$total_price = 0;
					$total_price_qty = 0;
					$total_earn = 0;
						
					// Get totals
					foreach ( $prices as $price => $data ) {
						$total_qty += $data['qty'];
						$total_credits += $data['credits'];
						$total_price += $price;
						$total_price_qty += $price*$data['qty'];
						$total_earn += ( $prices[$price]['qty'] * $price ) - $prices[$price]['credits'];
					}
					$average_price = ($total_price_qty/$total_qty);			
					// Records are based on the deal and the results of all of it's purchases.
					$purchase_array[] = apply_filters( 'gb_sales_summary_record_item', array(
							'deal_id' => $deal_id,
							'deal_name' => $deal_title,
							'qty' => $total_qty,
							'price' => self::gb_get_formatted_money( $average_price ),
							'credits' => $total_credits,
							'earn' => $total_earn
						), $deal );
				}

				// Modify the deal title if these next items are sub-items
				if ( $multiple ) {
					$deal_title = ' &rsaquo; <em>' . $deal_title . '</em>';
				}

				// Loop through each price and make an entry.
				foreach ( $prices as $price => $data ) {
					// Records are based on the deal and the results of all of it's purchases.
					$purchase_array[] = apply_filters( 'gb_sales_summary_record_item', array(
							'deal_id' => $deal_id,
							'deal_name' => $deal_title,
							'qty' => $prices[$price]['qty'],
							'price' => gb_get_formatted_money( $price ),
							'credits' => $prices[$price]['credits'],
							'earn' => ( $prices[$price]['qty'] * $price ) - $prices[$price]['credits']
						), $deal );
				}

			} // end loop of deals
		}
		$report->records = apply_filters( 'set_sales_summary_report_records', $purchase_array );
	}
	
	private static function gb_get_formatted_money( $amount, $decimals = TRUE ) {
		$orig_amount = $amount;
		$symbol = gb_get_currency_symbol( FALSE );
		$number = number_format( floatval( $amount ), 2 );
		if ( strstr( $symbol, '%' ) ) {
			$string = str_replace( '%', $number, $symbol );
		} else {
			$string = $symbol . $number;
		}
		if ( $number < 0 ) {
			$string = '-'.str_replace( '-', '', $string );
		}
		if ( !$decimals ) {
			$string = str_replace('.00','', $string);
		}
		return $string;
	}

}

// Initiate the add-on
class Group_Buying_Reports_SS_Addon extends Group_Buying_Controller {

	public static function init() {
		// Hook this plugin into the GBS add-ons controller
		add_filter( 'gb_addons', array( get_class(), 'gb_add_on' ), 10, 1 );
	}

	public static function gb_add_on( $addons ) {
		if ( self::using_permalinks() ) {
			$link = add_query_arg( array( 'report' => 'sales_summary' ), home_url( trailingslashit( get_option( Group_Buying_Reports::REPORTS_PATH_OPTION ) ) ) );
		} else {
			$link = add_query_arg( array( Group_Buying_Reports::REPORT_QUERY_VAR => 1, 'report' => 'sales_summary'  ), home_url() );
		}
		$addons['gb_sales_summary_reporting'] = array(
			'label' => self::__( 'Sales Summary Report' ),
			'description' => sprintf( self::__( 'Generate sales summary. <a href="%s" class="button">Report</a>' ), $link ),
			'files' => array(
				__FILE__,
				dirname( __FILE__ ) . '/library/template-tags.php',
			),
			'callbacks' => array(
				array( 'Group_Buying_Reports_SS', 'init' ),
			),
		);
		return $addons;
	}

}