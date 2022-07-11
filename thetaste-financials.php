<?php

/*
    Plugin Name: TheTaste Financials Plugin
    Plugin URI: http://thetaste.ie
    Description: Various functionalities for theTaste.ie financial
							   reporting and bank extract code, including building 
								 an order transaction table
		Version: 1.0.0
		Date: 6/18/2021
    Author: Ron Boutilier
    Text Domain: taste-plugin
 */

defined('ABSPATH') or die('Direct script access disallowed.');

define('TFINANCIAL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TFINANCIAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TFINANCIAL_PLUGIN_INCLUDES', TFINANCIAL_PLUGIN_PATH.'includes');
define('TFINANCIAL_PLUGIN_INCLUDES_URL', TFINANCIAL_PLUGIN_URL.'includes');
define('TFINANCIAL_PLUGIN_LOGS_PATH', TFINANCIAL_PLUGIN_PATH.'logs');


require_once TFINANCIAL_PLUGIN_INCLUDES.'/activation-deactivation.php';

register_activation_hook( __FILE__, 'tfinancial_activation' );
register_deactivation_hook( __FILE__, 'tfinancial_deactivation' );

if (is_admin()) {
	/*
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-products-by-venue.php';
	VenueUserFields::get_instance();
	*/
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/Taste_list_table.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/tf-view-order-trans-page.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/tf-admin-menus.php';
}

// enqueues 
//require_once TFINANCIAL_PLUGIN_INCLUDES.'/enqueues.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/ajax-functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/real-time-trans-build.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/trans-insert-functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-table-bulk.php';

/* some helpful CONSTANTS */
define('TASTE_PAYMENT_STATUS_PAID', 1);
define('TASTE_PAYMENT_STATUS_ADJ', 2);
define('TASTE_PAYMENT_STATUS_PENDING', 3);
define('TASTE_PAYMENT_STATUS_PROCESSING', 4);
define('TASTE_DEFAULT_PAYMENT_STATUS', TASTE_PAYMENT_STATUS_PAID);

/**
 * Page Templates setup code
 */
// set up page templates
function tfinancial_add_template ($templates) {
	$templates['test-build-trans-bulk.php'] = 'Build Transaction Table';
	return $templates;
	}
add_filter ('theme_page_templates', 'tfinancial_add_template');

function tfinancial_redirect_page_template ($template) {
	if (is_page_template('test-build-trans-bulk.php')) {
		$template = plugin_dir_path( __FILE__ ).'page-templates/test-build-trans-bulk.php';
	}
	return $template;
}
add_filter ('page_template', 'tfinancial_redirect_page_template');




 /**********************************************
 * Code for hooks/filters goes here
 **********************************************/

function taste_hide_giftcert_price($price, $product) {

	if ($product->get_meta('hide_reg_price')) {
		$price_dom = new DOMDocument();
		if ( !$price_dom->loadHTML(mb_convert_encoding($price, 'HTML-ENTITIES'))) {
			return $price;
		}
		$del_price = $price_dom->getElementsByTagName('ins')->item(0);
		return $price_dom->saveXML($del_price);
		
	} else {
		return $price;
	}

}
add_filter( 'woocommerce_get_price_html', 'taste_hide_giftcert_price', 10, 2 );

 /*************************************************
 * Set up nightly cron job to update trans table
 *************************************************/

// // need to set up the cron job that will create the jobs-sitemap.xml above
// add_action('taste_nightly_event', 'taste_update_trans_table');

// add_filter( 'cron_schedules', 'taste_add_cron_interval' );
// function taste_add_cron_interval( $schedules ) {
//     $schedules['five_minutes'] = array(
//             'interval'  => 300, // time in seconds
//             'display'   => 'five_minutes'
//     );
//     return $schedules;
// }


// function taste_nightly_cron_activation() {
// 	// build start time for 12:01am
// 	$start_time = strtotime(date('Y-m-d 00:01'));
	
// 	if ( !wp_next_scheduled( 'taste_nightly_event' ) ) {
// 		wp_schedule_event( time(), 'hourly', 'taste_nightly_event');
// 		// wp_schedule_event( time(), 'five_minutes', 'taste_nightly_event');
// 	}
// }
// add_action('wp', 'taste_nightly_cron_activation');

// function taste_update_trans_table() {
	
// require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-bulk-cron.php';
	
// }