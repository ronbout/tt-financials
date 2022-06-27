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


require_once TFINANCIAL_PLUGIN_INCLUDES.'/activation-deactivation.php';

register_activation_hook( __FILE__, 'tfinancial_activation' );
register_deactivation_hook( __FILE__, 'tfinancial_deactivation' );

if (is_admin()) {
	/*
	require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-products-by-venue.php';
	VenueUserFields::get_instance();
	*/
}

// enqueues 
//require_once TFINANCIAL_PLUGIN_INCLUDES.'/enqueues.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/ajax-functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/real-time-trans-build.php';

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