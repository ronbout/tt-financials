<?php
/**
 *  tf-admin-menus.php 
 *  Sets up the admin menus
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

 function tf_submenu_options() {

	 add_submenu_page(
		'woocommerce',
		__('Order Transactions'),
		__('Transactions'),
		'manage_options',
		'view-order-transactions',
		'tf_view_order_trans'
	 );
 }

add_action('admin_menu', 'tf_submenu_options', 99);
