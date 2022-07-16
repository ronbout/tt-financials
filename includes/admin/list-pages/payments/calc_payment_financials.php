<?php
/**
 * 	calc_payments_financials.php
 * 	code for the add_payment_financials method
 *	of the payments admin list page
 *	
 *	This code will just be inserted into that method
 *	It has $payment_rows as the parameter that will
 *	be available in this file
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

global $wpdb;

/**
 * 
 * 
 *   for now, just get one line per payment and ignore rest
 */
$unique_payment_list = array();
$unique_payment_rows = array_filter($payment_rows, function ($row) use (&$unique_payment_list) {
	// print_r($row);
	// print_r($unique_payment_list);
	if (in_array($row['payment_id'], $unique_payment_list)) {
		return false;
	} else {
		$unique_payment_list[] = $row['payment_id'];
		return true;
	}
});


// echo "<pre>";
// print_r($payment_rows);
// print_r($unique_payment_rows);
// echo "</pre>";

// get list of products

//  retrieve prod info price, comm rate

// 