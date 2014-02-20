<?php
/*
Plugin Name: Group Buying Addon - Reporting (Sales Summary) with email delivery
Version: 1.2.1
Plugin URI: http://groupbuyingsite.com/marketplace
Description: Adds a custom GBS report
Author: Sprout Venture
Author URI: http://sproutventure.com/wordpress
Plugin Author: Dan Cameron
Contributors: Dan Cameron 
Text Domain: group-buying
Domain Path: /lang

*/
define ('GB_REPORT_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );

add_action('plugins_loaded', 'gb_load_custom_reporting');
function gb_load_custom_reporting() {
	if ( class_exists('Group_Buying_Controller') ) {
		require_once('classes/GB_Report_Addon.php');
		add_filter( 'gb_addons', array( 'GB_Report_Addon', 'gb_addon' ), 10, 1 );
	}
}