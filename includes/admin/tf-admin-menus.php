<?php
/**
 *  tf-admin-menus.php 
 *  Sets up the admin menus
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');
global $tf_trans_table, $tf_venues_table;

 function tf_submenu_options() {

	 $trans_list_page = add_submenu_page(
		'woocommerce',
		__('Order Transactions'),
		__('Transactions'),
		'manage_options',
		'view-order-transactions',
		'tf_view_order_trans'
	 );

	 add_action("load-$trans_list_page", 'tf_add_trans_page_options');
	 
	 $venues_list_page = add_submenu_page(
		'woocommerce',
		__('Venues'),
		__('Venues'),
		'manage_options',
		'view-venues',
		'tf_view_venues'
	 );

	 add_action("load-$venues_list_page", 'tf_add_venues_page_options');
 }

add_action('admin_menu', 'tf_submenu_options', 99);

function tf_add_trans_page_options() {
global $tf_trans_table;
	$args = array( 
		'label' => 'Transactions Per Page: ',
		'default' => 20,
		'option' => 'tf_trans_rows_per_page',
	);
	add_screen_option('per_page', $args);

	$tf_trans_table = new TFTRans_list_table();
}

function tf_add_venues_page_options() {
	global $tf_venues_table;
		$args = array( 
			'label' => 'Venues Per Page: ',
			'default' => 20,
			'option' => 'tf_venues_rows_per_page',
		);
		add_screen_option('per_page', $args);
	
		$tf_venues_table = new TFVenues_list_table();
	}

function tf_set_screen_option($status, $option, $value) {
	if (in_array($option, array('tf_trans_rows_per_page', 'tf_venues_rows_per_page'))) {
		return $value;
	}
}

add_filter('set-screen-option', 'tf_set_screen_option', 11, 3);
