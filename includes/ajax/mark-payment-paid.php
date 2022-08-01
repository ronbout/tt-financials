<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function mark_payment_paid($payment_list) {
	global $wpdb;

	$placeholders = array_fill(0, count($payment_list), '%d');
	$placeholders = implode(', ', $placeholders);

	$sql = "
		UPDATE {$wpdb->prefix}taste_venue_payment pay
		SET pay.status = " . TASTE_PAYMENT_STATUS_PAID . "
		WHERE pay.id IN ($placeholders)
	";

	$sql = $wpdb->prepare($sql, $payment_list);

	$rows_affected = $wpdb->query($sql);
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update payment status. \n' . $wpdb->last_error);
	} else {
		$ret_json = array(
			'success' => true,
		);	
	}

	echo wp_json_encode($ret_json);
	return;
}