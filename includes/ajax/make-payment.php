<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function make_payment($payment_info) {
	global $wpdb;
	$payment_date = date("Y-m-d H:i:s");
	$payment_status = TASTE_PAYMENT_STATUS_PENDING;

	foreach($payment_info as $venue_id => $venue_prods) {
		$total_amount = array_reduce($venue_prods, function ($tot, $prod_info) {
			$tot += $prod_info['paymentAmt'];
			return $tot;
		},0);
		$product_order_info = array();
		foreach($venue_prods as $prod_info) {
			$product_order_info[$prod_info['productId']] = array(
				'amount' => $prod_info['paymentAmt'],
				'order_list' => $prod_info['orderInfo'],
			);
		}
		$payment_fields = array(
			'venue_id' => $venue_id,
			'payment_amount' => $total_amount,
			'payment_date' => $payment_date,
			'product_order_info' => $product_order_info,
			'payment_status' => $payment_status,
		);

		$db_insert_result = insert_payment($payment_fields);
		$db_status = $db_insert_result['db_status'];
		if (!$db_status) {
			return;
		}
		$payment_id =$db_insert_result['payment_id'];

		$hook_payment_info = array(
			'payment_date' => $payment_date,
			'payment_status' => $payment_status,
			'edit_mode' => 'INSERT',
			'order_item_ids' => $prod_info['orderInfo'],
		);
	
		do_action('tf_venue_page_payment_insert', $payment_id, $hook_payment_info);
		
		/*****  AUDIT TABLE UPDATE ******/
		$payment_audit_table = $wpdb->prefix ."taste_venue_payment_audit";
		$user_id = get_current_user_id();

		$data = array(
			'payment_id' => $payment_id,
			'prev_payment_timestamp' => NULL,
			'payment_timestamp' => $payment_date,
			'user_id' => $user_id,
			'action' => 'INSERT',
			'prev_amount' =>  NULL,
			'amount' => $total_amount,
			'comment' => '',
		);

		$format = array('%d', '%s', '%s', '%d', '%s', '%f', '%f', '%s');

		$rows_affected = $wpdb->insert($payment_audit_table, $data, $format);	


		// if not success set error array and return
		if (!$rows_affected) {
			$ret_json = array('error' => 'Could not update payment audit table. \n' . $wpdb->last_error);
			echo wp_json_encode($ret_json);
			return;
		}

	}

	$ret_json = array(
		'success' => true,
	);	
	echo wp_json_encode($ret_json);
	return;
}

function insert_payment($payment_fields) {
	global $wpdb;

	$payment_order_xref_table = $payment_fields['payment_order_xref_table'];
	$payment_status = $payment_fields['payment_status'];
	$payment_amount = $payment_fields['payment_amount'];
	$payment_date = $payment_fields['payment_date'];
	$venue_id = $payment_fields['venue_id'];
	$product_order_info = $payment_fields['product_order_info'];
	
	$wpdb->query( "START TRANSACTION" );

	// main payment table:  wp_taste_venue_payment
	$data = array(
		'venue_id' => $venue_id,
		'payment_date' => $payment_date,
		'amount' => $payment_amount,
		'status' => $payment_status,
	);
	$payment_table = $wpdb->prefix . 'taste_venue_payment';

	$format = array( '%d', '%s', '%f', '%d');
	$rows_affected = $wpdb->insert($payment_table, $data, $format);	
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return array('db_status' => false);
	}
	$payment_id = $wpdb->insert_id;

	// payment x product_id table: wp_taste_venue_payment_products
	$insert_values = '';
	$insert_parms = [];
	
	foreach ($product_order_info as $prod_id => $prod_info) {
		$insert_values .= '(%d, %d, %f),';
		$insert_parms[] = $payment_id;
		$insert_parms[] = $prod_id;
		$insert_parms[] = $prod_info['amount'];
	}
	$insert_values = rtrim($insert_values, ',');

	$sql = "INSERT into {$wpdb->prefix}taste_venue_payment_products
						(payment_id, product_id, amount)
					VALUES $insert_values";

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $insert_parms)
	);
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not update Payment Product Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return array('db_status' => false);
	}

	// payment x orders table: wp_taste_venue_payment_order_item_xref
	$insert_values = '';
	$insert_parms = [];
	
	foreach ($product_order_info as $prod_info) {
		foreach($prod_info['order_list'] as $order_item_id) {
			$insert_values .= '(%d, %d),';
			$insert_parms[] = $payment_id;
			$insert_parms[] = $order_item_id;
		}

	}
	$insert_values = rtrim($insert_values, ',');
	
	$sql = "INSERT into {$wpdb->prefix}taste_venue_payment_order_item_xref
						(payment_id, order_item_id)
					VALUES $insert_values";

	$rows_affected = $wpdb->query(
		$wpdb->prepare($sql, $insert_parms)
	);
	// if not success set error array and return
	if (!$rows_affected) {
		$ret_json = array('error' => 'Could not insert row into Payment Order Xref Table. ' . $wpdb->last_error);
		echo wp_json_encode($ret_json);
		$wpdb->query("ROLLBACK");
		return array('db_status' => false);
	}


	$wpdb->query( "COMMIT" );

	return array(
		'db_status' => true,
		'payment_id' => $payment_id,
	);
}