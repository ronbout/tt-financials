<?php

/*
    Plugin Name: Thetfinancial Financials Plugin
    Plugin URI: http://thetaste.ie
    Description: Various functionalities for thetfinancial.ie financial
							   reporting and bank extract code, including building 
								 an order transaction table
		Version: 1.0.0
		Date: 6/18/2021
    Author: Ron Boutilier
    Text Domain: tfinancial-plugin
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
/*
require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/ajax-functions.php';
require_once TFINANCIAL_PLUGIN_INCLUDES.'/functions.php';

require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/outstanding/ajax-functions.php';
*/

/**
 * Campaign manager set up code
 */
// set up page templates
// function tfinancial_add_venue_manager_template ($templates) {
// 	$templates['campaign-manager.php'] = 'Campaign Manager';
// 	$templates['venue-portal.php'] = 'Venue Portal';
// 	$templates['venue-profile-page.php'] = 'Venue Profile Page';
// 	$templates['audit-by-products.php'] = 'Audit By Products';
// 	return $templates;
// 	}
// add_filter ('theme_page_templates', 'tfinancial_add_venue_manager_template');

// function tfinancial_redirect_page_template ($template) {
// 	if (is_page_template('campaign-manager.php')) {
// 		$template = plugin_dir_path( __FILE__ ).'page-templates/campaign-manager.php';
// 	}
// 	if (is_page_template('venue-portal.php')) {
// 		$template = plugin_dir_path( __FILE__ ).'page-templates/venue-portal.php';
// 	}
// 	if (is_page_template('venue-profile-page.php')) {
// 		$template = plugin_dir_path( __FILE__ ).'page-templates/venue-profile-page.php';
// 	}
// 	if (is_page_template('audit-by-products.php')) {
// 		$template = plugin_dir_path( __FILE__ ).'page-templates/audit-by-products.php';
// 	}
// 	return $template;
// }
// add_filter ('page_template', 'tfinancial_redirect_page_template');

// // make sure the campaign manager login does not redirect to wp-admin
// add_action( 'wp_login_failed', 'tfinancial_login_fail' );  // hook failed login

// function tfinancial_login_fail( $username ) {
//    $referrer = $_SERVER['HTTP_REFERER'];  
//    // if there's a valid referrer, and it's not the default log-in screen
//    if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') ) {
//       wp_redirect( $referrer . '?login=failed' );  // let's append some information (login=failed) to the URL for the theme to use
//       exit;
//    }
// }
// function tfinancial_query_vars( $qvars ) {
// 	$qvars[] = 'login';
// 	return $qvars;
// }
// add_filter( 'query_vars', 'tfinancial_query_vars' );
 