<?php
/**
 * 
 *  trans-insert-functions.php
 * 
 *  Code to insert trans records given an array of order data
 * 
 *  06/27/2022
 *  Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

/***********************************************************
*   Functions for Redeemed / UnRedeemed transaction types
************************************************************/

function process_redeemed_order_list($redeemed_order_rows, $redeem_flg=1, $formatted_date='') {

  $prod_ids = array_unique(array_column($redeemed_order_rows, 'product_id'));
  $prod_data = build_product_data($prod_ids);

  // build insert data 
  $rows_affected = insert_redeemed_trans_rows($redeemed_order_rows, $prod_data, $redeem_flg, $formatted_date);

  return $rows_affected;

	/*****  TODO:  Error Checking - need log file for errors */

}

function insert_redeemed_trans_rows($redeemed_order_rows, $prod_data, $redeem_flg=1, $formatted_date='') {
	global $wpdb;

	$trans_table = "{$wpdb->prefix}taste_order_transactions";

	$sql = "
		INSERT INTO $trans_table
		(	order_id, order_item_id, transaction_date, trans_type, trans_amount, order_date, product_id,
			product_price, quantity, gross_revenue, venue_id, venue_name, creditor_id, 
			venue_creditor, coupon_id, coupon_code, coupon_value, net_cost, commission,
			vat, gross_income, venue_due, redemption_date )
		VALUES 
	";

	$prepare_values = array();
	
	$prev_order_id = -999;
	$order_cnt = count($redeemed_order_rows);

	for($key = 0; $key < $order_cnt; $key++) {
		$order_info = $redeemed_order_rows[$key];
		$order_id = $order_info['order_id'];
		$product_id = $order_info['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$venue_id = $prod_data[$product_id]['venue_id'];
		$venue_name = $prod_data[$product_id]['venue_name'];
		$quantity = $redeem_flg ? $order_info['item_qty'] : (int) $order_info['item_qty'] * -1;

		$curr_prod_values = tf_calc_net_payable($product_price, $product_vat, $product_comm, $quantity, true);
		$gross_revenue = $curr_prod_values['gross_revenue'];
		$commission = $curr_prod_values['commission'];
		$vat = $curr_prod_values['vat'];
		$venue_due = $curr_prod_values['net_payable'];

		$gross_income = $vat + $commission;
		$coupon_value = $redeem_flg ? $order_info['coupon_amount'] : $order_info['coupon_amount'] * -1;
		$net_cost = $gross_revenue - $coupon_value;
		$creditor_id = $venue_id;
		$venue_creditor = $venue_name;
		$redeem_date = $formatted_date ? $formatted_date : $order_info['redeem_date'];
		
		// need to check for any coupon code's that match a previous order.  
		// Those are store credit coupons and orders created with them, get
		// a different transaction code:  "Redemption - From Credit"
		// and the store credit amount goes in the trans_amount field

		if ($prev_order_id !== $order_id) {
			if ($order_info['coupon_ids']) {
				$order_rows_with_credit_amts = check_redeemed_for_store_credit_coupon($redeemed_order_rows, $order_info, $order_id, $prod_data);

				// put order items w/ updated store credit amount info back into the array
				// and update the current order info
				$order_info['store_credit_amount'] = $order_rows_with_credit_amts[$key]['store_credit_amount'];
				foreach($order_rows_with_credit_amts as $upd_key => $upd_row) {
					$redeemed_order_rows[$upd_key]['store_credit_amount'] = $upd_row['store_credit_amount'];
				}
			}
			
			$prev_order_id = $order_id;
		}

		if (isset($order_info['store_credit_amount']) && $order_info['store_credit_amount'] ) {
			$trans_code = $redeem_flg ? "Redemption - From Credit" : "UnRedeem";
			$trans_amount = $redeem_flg ? $order_info['store_credit_amount'] : $order_info['store_credit_amount'] * -1 ;
		} else {
			$trans_code = $redeem_flg ? "Redemption": "UnRedeem";
			$trans_amount = $gross_revenue;  //  gross_revenue will already be negative (- qty) if UnRedeem
    }

		$sql .= "(%d, %d, %s, %s, %f, %s, %d, %f, %d, %f, %d, %s, %d,
			 %s, %s, %s, %f, %f, %f, %f, %f, %f, %s),";

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], $redeem_date, $trans_code, 
			$trans_amount, 	$order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, 
			$venue_name,	$creditor_id, $venue_creditor, $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, 
			$net_cost, $commission, $vat, $gross_income, $venue_due, $redeem_date);

	}

	$sql = trim($sql, ',');
	
	$prepared_sql = $wpdb->prepare($sql, $prepare_values);
	$prepared_sql = str_replace("''",'NULL', $prepared_sql);
	$rows_affected = $wpdb->query($prepared_sql);

	return $rows_affected;

}


function check_redeemed_for_store_credit_coupon($order_rows, $order_info, $order_id, $prod_data) {
	// a near duplicate for check_for_store_credit_coupon(), but need to deal with
	// the fact that not all order items will have been read for the order, so if
	// need to calc against total cost, will have to read in info from tables
	
	// TODO:  *** consolidate the to check_*_for_store_credit functions ****

	global $wpdb;

	$store_credit_count = 0;
	$total_credit_amount = 0;

	$coupon_ids = $order_info['coupon_ids'];
	$coupon_codes = $order_info['coupon_codes'];

	$order_array = array_filter($order_rows, function ($order_item_row) use ($order_id) {
		return $order_item_row['order_id'] === $order_id;
	} );
	
	if (! $coupon_ids) {
		foreach($order_array as &$order_item) {
			$order_item['store_credit_amount'] = 0;
		}
		return $order_array;
	}

	// coupon codes are comma delimited listing
	$coupon_ids_array = explode(',', $coupon_ids);
	$coupon_codes_array = explode(',', $coupon_codes);
	foreach($coupon_ids_array as $ckey => $coupon_id) {
		$coupon_code = $coupon_codes_array[$ckey];
		if (is_numeric($coupon_code)) {
			$sql = "
			SELECT count(order_p.ID) as order_found, oc_look.discount_amount
			FROM {$wpdb->prefix}posts coup_p 
			JOIN {$wpdb->prefix}posts order_p ON order_p.ID = coup_p.post_title
			JOIN {$wpdb->prefix}wc_order_coupon_lookup oc_look ON oc_look.order_id = %d 
					AND oc_look.coupon_id = coup_p.ID
			WHERE coup_p.ID = %d
			";

			$order_check = $wpdb->get_results( 
											$wpdb->prepare($sql, $order_id, $coupon_id), ARRAY_A);
			if ($order_check[0]['order_found']) {
				$store_credit_count += 1;
				$total_credit_amount += $order_check[0]['discount_amount'];
			}
		}
	}

	if (!$store_credit_count) {
		foreach($order_array as &$order_item) {
			$order_item['store_credit_amount'] = 0;
		}
	} else {
		$coupon_count = count($coupon_ids_array);
		if ($coupon_count === 1 || $store_credit_count === $coupon_count) {
			foreach($order_array as &$order_item) {
				$order_item['store_credit_amount'] = $order_item['coupon_amount'];
			}
		} else {
			// get the totals for all order items from the tables
			// unlike new orders, not all order items have been retrieved
			// in the initial SQL
			$total_gross_revenue = 0;

			$sql = "
				SELECT ROUND(SUM(opl.product_gross_revenue + opl.coupon_amount),2) AS total_cost
					FROM {$wpdb->prefix}wc_order_product_lookup opl
					WHERE opl.order_id = %d
			";

			$total_gross_revenue_row = $wpdb->get_results( 
											$wpdb->prepare($sql, $order_id), ARRAY_A);

			$total_gross_revenue = $total_gross_revenue_row[0]['total_cost'];

			// now loop through each item, assigning the appropriate
			// % of total store credit to each item
			foreach($order_array as &$order_item) {
				$product_id = $order_item['product_id'];
				$product_price = $prod_data[$product_id]['price'];
				$product_comm = $prod_data[$product_id]['commission'];
				$product_vat = $prod_data[$product_id]['vat'];
				$quantity = $order_item['item_qty'];
				$gross_revenue = $quantity * $product_price;
				$commission = ($gross_revenue / 100 ) * $product_comm;
				$vat = ($commission / 100) * $product_vat;
				$order_item['gross_revenue'] = $gross_revenue;
				$order_item['store_credit_amount'] = ($order_item['gross_revenue'] / $total_gross_revenue) * $total_credit_amount;
			}
		}
	}
	return $order_array;
}
/***********************************************************
*   End of Redeemed / UnRedeemed transaction types
************************************************************/

/***********************************************************
*   Functions for all transaction types
************************************************************/

function build_product_data($prod_ids) {
	global $wpdb;

	// get the product-specific info
	$pid_placeholders = array_fill(0, count($prod_ids), '%d');
	$pid_placeholders = implode(', ', $pid_placeholders);

	$product_rows = $wpdb->get_results($wpdb->prepare("
			SELECT  pm.post_id, v.venue_id, v.name AS venue_name,
							MAX(CASE WHEN pm.meta_key = '_sale_price' then pm.meta_value ELSE NULL END) as price,
							MAX(CASE WHEN pm.meta_key = 'vat' then pm.meta_value ELSE NULL END) as vat,
							MAX(CASE WHEN pm.meta_key = 'commission' then pm.meta_value ELSE NULL END) as commission
			FROM   {$wpdb->prefix}postmeta pm
			JOIN {$wpdb->prefix}posts p ON p.id = pm.post_id
			LEFT JOIN {$wpdb->prefix}taste_venue_products vp ON vp.product_id = pm.post_id
			LEFT JOIN {$wpdb->prefix}taste_venue v ON v.venue_id = vp.venue_id
			WHERE pm.post_id in ($pid_placeholders)                 
			GROUP BY
				pm.post_id
		", $prod_ids), ARRAY_A);

	return array_column($product_rows, null, 'post_id' );

}
