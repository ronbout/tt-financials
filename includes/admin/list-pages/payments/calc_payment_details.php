<?php
/**
 * 	calc_payments_details.php
 * 	code for the add_payment_details method
 *	of the payments admin list page
 *	
 *	This code will just be inserted into that method
 *	It has $payment_rows as the parameter that will
 *	be available in this file
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

global $wpdb;

function build_details($payment_row) {
	$tmp_row = $payment_row;
	$tmp_row['details'] = "
		<td colspan='7'>details</td>

	";
	$tmp_row['actions'] = $payment_row['payment_id'];
	return $tmp_row;
}

// get list of products

//  retrieve prod info price, comm rate, order ids, order item ids

// build table row of that info

// add to "details" key of the payment_rows

$payment_rows_w_details = array_map('build_details', $payment_rows);
