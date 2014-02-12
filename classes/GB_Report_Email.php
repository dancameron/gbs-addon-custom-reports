<?php 

/**
* Periodically send the SS report to a few users.
*/
class GB_Report_Email extends Group_Buying_Controller {
	const EMAIL_SENT_OPTION = 'gb_last_time_summary_sales_email_was_sent_v2';
	const EMAIL_SENT_RECIPIENTS = 'gb_summary_sales_email_recipients';
	const EMAIL_SENT_TOD = 'gb_summary_sales_email_tod';
	private static $last_email_sent = 0;
	private static $recipients = 0;
	private static $send_tod = '';
	
	public static function init() {
		self::$last_email_sent = get_option( self::EMAIL_SENT_OPTION, 0 );
		self::$recipients = get_option( self::EMAIL_SENT_RECIPIENTS, get_option('admin_email') );
		self::$send_tod = get_option( self::EMAIL_SENT_TOD, '10pm, 3pm' );
		self::register_options();

		// Notifications
		add_filter( 'gb_notification_types', array( get_class(), 'register_notifications' ) );
		add_filter( 'gb_notification_shortcodes', array( get_class(), 'register_notification_shortcodes' ) );

		// cron to send emails
		if ( GBS_DEV ) {
			add_action( 'init', array( get_class(), 'maybe_send_reports' ) );
		} else {
			add_action( self::CRON_HOOK, array( get_class(), 'maybe_send_reports' ) );
		}
		add_action( 'admin_init', array( get_class(), 'maybe_send_reports' ) );
	}

	function __construct() {}

	///////////////
	// Options //
	///////////////


	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_options() {

		// UI Settings
		$settings = array(
			'gb_summary_report_emails' => array(
				'title' => self::__( 'Summary Email Report Options' ),
				'weight' => 10,
				'settings' => array(
					self::EMAIL_SENT_RECIPIENTS => array(
						'label' => self::__( 'Recipients' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$recipients,
							'description' => self::__( 'Comma separated emails.' )
							)
						),
					self::EMAIL_SENT_TOD => array(
						'label' => self::__( 'Time of day to send reports.' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$send_tod,
							'description' => self::__( 'Comma separated times of day, e.g. 3pm, 3am, 10:30am, etc..<br/>Current Time: ' ) . date('g:ia', current_time('timestamp') )
							)
						)
					)
				),
			);
		do_action( 'gb_settings', $settings, Group_Buying_UI::SETTINGS_PAGE );
	}


	////////////////////
	// Notifications //
	////////////////////

	public function maybe_send_reports() {
		if ( !self::$last_email_sent ) {
			self::update_last_email_sent_time( current_time('timestamp' ) );
		}
		$times_of_day = explode(', ', self::$send_tod );
		foreach ( $times_of_day as $sttime ) {
			$time_to_send = strtotime( 'today ' . $sttime );
			// after the last send
			if ( $time_to_send > self::$last_email_sent ) {
				// meant to be sent already
				if ( $time_to_send <= current_time('timestamp') ) {
					self::send_reports( $time_to_send );
				}
			}
		}
	}

	public static function send_reports( $time_to_send = 0 ) {
		$recipients = explode(', ', self::$recipients );
		foreach ( $recipients as $recipient ) {
			$user_id = 0;
			$user = get_user_by( 'email', $recipient );
			if ( $user && isset( $user->ID ) ) {
				$user_id = $user->ID;
			}
			$data = array(
				'user_id' => $user_id,
				'time_to_send' => $time_to_send
			);
			Group_Buying_Notifications::send_notification( 'summary_sales_report', $data, $recipient );
		}
		self::update_last_email_sent_time( $time_to_send );
		do_action( 'gb_summary_email_reports_sent', $time_to_send );
	}


	public function register_notifications( $default_notifications ) {
		$summary_sales_notifications = array(
				'summary_sales_report' => array(
					'name' => self::__( 'Summary Sales Notification' ),
					'description' => self::__( 'Customize the summary sales report.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'site_title', 'site_url', 'summary_report' ),
					'default_title' => self::__( 'Summary Sales Report at ' . get_bloginfo( 'name' ) ),
					'default_content' => "Here's your summary sales report:\n\n\n[summary_report]",
					'allow_preference' => FALSE,
					'item_specific' => FALSE
				),
			);
		return array_merge( $default_notifications, $summary_sales_notifications );
	}

	public function register_notification_shortcodes( $default_shortcodes ) {
		$summary_report_shortcodes = array(
				'summary_report' => array(
					'description' => self::__( 'Used to display the summary report.' ),
					'callback' => array( get_class(), 'shortcode_summary_report' )
				),
			);
		return array_merge( $default_shortcodes, $summary_report_shortcodes );
	}

	public static function shortcode_summary_report( $atts, $content, $code, $data ) {
		if ( isset( $data['time_to_send'] ) && isset( $data['user_id'] ) ) {
			$time_to_send = $data['time_to_send'];
			$account_merchant_id = gb_account_merchant_id( $data['user_id'] );
			return self::report_content( $time_to_send, $account_merchant_id );
		}
		return '';
	}

	//////////////
	// Utility //
	//////////////

	public function report_content( $time_of_summary_report = 0, $account_merchant_id = 0, $filter = 'publish' ) {
		$columns = GB_Reports_SS::get_summary_columns();
		$time_of_summary_report = date( 'm/d/Y', $time_of_summary_report );
		$records = GB_Reports_SS::get_purchase_array( $account_merchant_id, $time_of_summary_report, $time_of_summary_report, $filter );
		$report = self::load_view_to_string( 'reports/view', array( 'columns' => $columns, 'records' => $records ) );
		$report = str_replace( '<span id="ff_desc" class="contrast_light message clearfix">Search the entire report, sort columns then download the CSV of the filtered report.</span>', '', $report );
		$report = str_replace( '<p><input name="filter" id="filter_box" value="" maxlength="30" size="30" type="text" placeholder="Filter" class="text-input"> <input id="filter_clear_button" type="submit" value="Clear" class="alt_button"/></p>', '', $report );
		return $report;
	}

	public function update_last_email_sent_time( $time ) {
		self::$last_email_sent = $time;
		update_option( self::EMAIL_SENT_OPTION, $time );
	}

}