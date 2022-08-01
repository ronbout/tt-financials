<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

function tf_ajax_build_trans_bulk() {

	if (!check_ajax_referer('tf-admin-ajax-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	$start_date = $_POST['start_date'];
	$delete_flag = isset($_POST['delete_flag']) ? $_POST['delete_flag'] : 0;

	require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-table-bulk.php';
	build_trans_table_bulk($start_date, false, $delete_flag);

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

function tf_ajax_payments_page_mark_paid() {

	if (!check_ajax_referer('tf-admin-ajax-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}
	if (!isset($_POST['payment_list'])) {
		echo 'Missing valid payment info';
		wp_die();
	}

	$payment_list = $_POST['payment_list'];

	require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/mark-payment-paid.php';
	mark_payment_paid($payment_list);

	wp_die();
}

function tf_ajax_set_trans_cron() {

	if (!check_ajax_referer('tf-admin-ajax-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}
	if (!isset($_POST['cron_on_off']) || !isset($_POST['frequency'])) {
		echo 'Missing cron info';
		wp_die();
	}

	$cron_on_off = $_POST['cron_on_off'];
	$frequency = $_POST['frequency'];

	require_once TFINANCIAL_PLUGIN_INCLUDES.'/ajax/set-trans-cron.php';
	set_trans_cron($cron_on_off, $frequency);

	wp_die();
}

if ( is_admin() ) {
	add_action('wp_ajax_build_trans_bulk','tf_ajax_build_trans_bulk');
	add_action('wp_ajax_venues_page_make_payment','tf_ajax_venues_page_make_payment');
	add_action('wp_ajax_payments_page_mark_paid','tf_ajax_payments_page_mark_paid');
	add_action('wp_ajax_set_trans_cron','tf_ajax_set_trans_cron');
}