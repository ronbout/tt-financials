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
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/admin-enqueues.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-pages/Taste_list_table.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-pages/transactions/tf-view-order-trans-page.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-pages/venues/tf-view-venues-page.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-pages/payments/tf-view-payments-page.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/tf-admin-menus.php';
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/wc-settings/wc-settings.php';
}

// enqueues 
//require_once TFINANCIAL_PLUGIN_INCLUDES.'/enqueues.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/ajax-functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/real-time-trans-build.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/trans-insert-functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-table-bulk.php';

/* some helpful CONSTANTS */
!defined('TASTE_PAYMENT_STATUS_PAID') && define('TASTE_PAYMENT_STATUS_PAID', 1);
!defined('TASTE_PAYMENT_STATUS_ADJ') && define('TASTE_PAYMENT_STATUS_ADJ', 2);
!defined('TASTE_PAYMENT_STATUS_PENDING') && define('TASTE_PAYMENT_STATUS_PENDING', 3);
!defined('TASTE_PAYMENT_STATUS_PROCESSING') && define('TASTE_PAYMENT_STATUS_PROCESSING', 4);
!defined('TASTE_DEFAULT_PAYMENT_STATUS') && define('TASTE_DEFAULT_PAYMENT_STATUS', TASTE_PAYMENT_STATUS_PENDING);
define('TASTE_PBO_NET_PAYABLE_THRESHOLD', get_option('tf_financials_rounding_threshold', 0.1));
define('TASTE_PBO_BALANCE_FILTER_THRESHOLD', 0);
define('TASTE_TRANS_CRON_HOOK', 'taste_trans_cron_event');

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

 /***********************************************************
 * Set up Order Auto-complete when status set to processing
 ************************************************************/
add_action('woocommerce_order_status_changed', 'tf_auto_complete_by_payment_method');

function tf_auto_complete_by_payment_method($order_id) {
  if ( ! $order_id ) {
		return;
	}
	global $product;
	$order = wc_get_order( $order_id );

	if ('processing' == $order->data['status']) {
				$payment_method = $order->get_payment_method();
				if (! in_array($payment_method, array("cod", "cheque"))) {
					$order->update_status( 'completed' );
				}
			
	}
}
  
 /*************************************************
 * Set up nightly cron job to update trans table
 *************************************************/

// // need to set up the cron job that will create the jobs-sitemap.xml above
add_action(TASTE_TRANS_CRON_HOOK, 'tf_update_trans_table');

add_filter( 'cron_schedules', 'taste_add_cron_interval' );
function taste_add_cron_interval( $schedules ) {
    $schedules['two_hours'] = array(
            'interval'  => 7200, // time in seconds
            'display'   => 'Every Two Hours',
    );
    return $schedules;
}


// function taste_trans_cron_activation() {
// 	// build start time for 12:01am
// 	$start_time = strtotime(date('Y-m-d 00:01'));
	
// 	if ( !wp_next_scheduled( TASTE_TRANS_CRON_HOOK ) ) {
// 		wp_schedule_event( time(), 'daily', TASTE_TRANS_CRON_HOOK);
// 	}
// }
// add_action('wp', 'taste_trans_cron_activation');

function tf_update_trans_table() {
	
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-bulk-cron.php';
	
}