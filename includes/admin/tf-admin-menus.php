<?php
/**
 *  tf-admin-menus.php 
 *  Sets up the admin menus
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

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
 }

add_action('admin_menu', 'tf_submenu_options', 99);

function tf_add_trans_page_options() {
	$option = "per_page";

	$args = array( 
		'label' => 'Transactions Per Page: ',
		'default' => 20,
		'option' => 'tf_trans_rows_per_page',
	);
	add_screen_option($option, $args);
}

function tf_set_screen_option($status, $option, $value) {
	if ('tf_trans_rows_per_page' == $option) {
		return $value;
	}
}

add_filter('set-screen-option', 'tf_set_screen_option', 11, 3);
