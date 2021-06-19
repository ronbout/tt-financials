<?php 

	defined('ABSPATH') or die('Direct script access disallowed.');
	// Enqueue Jobs Page Stylesheets and Scripts

	// add_action('admin_enqueue_scripts', 'tfinancial_venue_load_admin_resources');

	// function tfinancial_venue_load_admin_resources($page) {

	// 	// if (in_array($page, array("user-new-php", "user-edit-php", "product_page_venue-assign-products"))) {
	// 		wp_enqueue_style( 'tfinancial-admin-css', tfinancial_PLUGIN_INCLUDES_URL."/css/thetfinancial-admin.css" );
	// 		$dep_array = ("product_page_venue-assign-products" === $page) ? array('jquery-ui-autocomplete') : array();
	// 		wp_enqueue_script( 'tfinancial-admin-js', tfinancial_PLUGIN_INCLUDES_URL . '/js/thetfinancial-admin.js', $dep_array, false, true);
	// 		wp_enqueue_script( 'tfinancial-venue-select-js', tfinancial_PLUGIN_INCLUDES_URL . '/js/thetfinancial-venue-select.js', $dep_array, false, true);
	// 		// wp_enqueue_script( 'tfinancial-admin-js', tfinancial_PLUGIN_INCLUDES_URL . '/js/thetfinancial-admin.min.js', $dep_array, false, true);
	// 		// wp_enqueue_script( 'tfinancial-venue-select-js', tfinancial_PLUGIN_INCLUDES_URL . '/js/thetfinancial-venue-select.min.js', $dep_array, false, true);
	// 	// }
	// }