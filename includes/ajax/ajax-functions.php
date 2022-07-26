<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

function tf_ajax_build_trans_bulk() {

	if (!check_ajax_referer('tf-admin-ajax-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	$start_date = $_POST['start_date'];

	require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-table-bulk.php';
	build_trans_table_bulk($start_date, false);

	wp_die();
}

function tf_ajax_venues_page_make_payment() {

	if (!check_ajax_referer('tf-admin-ajax-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}
	if (!isset($_POST['payment_info'])) {
		echo 'Missing valid payment info';
		wp_die();
	}

	$payment_info = $_POST['payment_info'];

	require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/make-payment.php';
	make_payment($payment_info);

	wp_die();
}

if ( is_admin() ) {
	add_action('wp_ajax_build_trans_bulk','tf_ajax_build_trans_bulk');
	add_action('wp_ajax_venues_page_make_payment','tf_ajax_venues_page_make_payment');
}