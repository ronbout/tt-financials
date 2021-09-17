<?php 
/**
 * 
 *  build-trans-table.php
 *  08/07/2021  Ron Boutilier
 * 
 *  build_trans_table function which will update the {$wpdb->prefix}taste_order_transactions
 *  table with any order transactions since the given date that have not already 
 *  been entered into the trans table.  This will most likely be run on a nightly
 *  basis.
 * 
 * 
 * 	TODO:  need log file for errors!!!
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

function build_trans_table($start_date = "2020-08-01") {

	process_new_orders($start_date);

	process_refunded_orders($start_date);

	process_redeemed_orders($start_date); 

	// process_payments($start_date);

	die();
	
}

function process_new_orders($start_date) {
	global $wpdb;

	// select orders with data and NOT in trans table 
	$sql = "
		SELECT wclook.order_id, wclook.order_item_id, oim.meta_value AS item_qty, 
			wclook.product_id, oi.downloaded, op.post_status AS order_status,
			GROUP_CONCAT( cpn_look.coupon_id ) AS coupon_ids,
			GROUP_CONCAT( cpn_post.post_title ) AS coupon_codes,
			wclook.coupon_amount, op.post_date AS order_date
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
		LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
		LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND wclook.date_created > %s	
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}taste_order_transactions ot
				WHERE ot.order_item_id = wclook.order_item_id
					AND ot.trans_type IN ('Order', 'Order - From Credit')
			)
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$new_order_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($new_order_rows)) {
		echo 'No new orders found';
		return;
	}
	$prod_ids = array_unique(array_column($new_order_rows, 'product_id'));
	$prod_data = build_product_data($prod_ids);

	// build insert data 
	$rows_affected = insert_order_trans_rows($new_order_rows, $prod_data);

	if ($rows_affected) {
		echo $rows_affected, " new order transaction rows inserted";
	} else {
		echo "Failure: ", $wpdb->last_error;
	}

	// insert and return  
	/*****  TODO:  Error Checking - need log file for errors */

}

function process_redeemed_orders($start_date) {
	global $wpdb;

	// select orders with redeemed data and NOT in trans table 
	$sql = "
		SELECT wclook.order_id, wclook.order_item_id, oim.meta_value AS item_qty, 
			wclook.product_id, oi.downloaded, op.post_status AS order_status,
			GROUP_CONCAT( cpn_look.coupon_id ) AS coupon_ids,
			GROUP_CONCAT( cpn_post.post_title ) AS coupon_codes,
			wclook.coupon_amount, op.post_date AS order_date
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
		LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
		LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND oi.downloaded = 1
			AND wclook.date_created > %s	
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}taste_order_transactions ot
				WHERE ot.order_item_id = wclook.order_item_id
					AND ot.trans_type = 'Redemption'
			)
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$redeemed_order_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($redeemed_order_rows)) {
		echo 'No Redeemed orders found';
		return;
	}

	$prod_ids = array_unique(array_column($redeemed_order_rows, 'product_id'));

	$prod_data = build_product_data($prod_ids);

	// var_dump($prod_data);

	// build insert data 
	$rows_affected = insert_redeemed_trans_rows($redeemed_order_rows, $prod_data);

	if ($rows_affected) {
		echo $rows_affected, " redeemed transaction rows inserted";
	} else {
		echo "Failure: ", $wpdb->last_error;
	}

	// insert and return  
	/*****  TODO:  Error Checking - need log file for errors */

}


function process_refunded_orders($start_date) {
	global $wpdb;

	// select orders with redeemed data and NOT in trans table 
	$sql = "
		SELECT wclook.order_id, wclook.order_item_id, oim.meta_value AS item_qty, 
			wclook.product_id, oi.downloaded, op.post_status AS order_status,
			GROUP_CONCAT( DISTINCT cpn_look.coupon_id ) AS coupon_ids,
			GROUP_CONCAT( DISTINCT cpn_post.post_title ) AS coupon_codes,
			ROUND(wclook.coupon_amount, 2) AS coupon_amount, op.post_date AS order_date,
			ROUND(SUM(ref_pm.meta_value) / (COALESCE ((COUNT( ref_p.ID) / COUNT(DISTINCT ref_p.ID)), 1)), 2)
				AS refund_total,
			GROUP_CONCAT(DISTINCT ref_p.ID) AS refund_ids,
			ROUND(SUM(ref_oim2.meta_value) / (COALESCE ((COUNT(ref_oim1.meta_id) / COUNT(DISTINCT ref_oim1.meta_id)), 1) ),2) * -1
				AS item_refund_amount
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}posts ref_p ON ref_p.post_parent = op.ID
			JOIN {$wpdb->prefix}postmeta ref_pm ON ref_pm.post_id = ref_p.ID AND ref_pm.meta_key = '_refund_amount'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta ref_oim1 ON ref_oim1.meta_key = '_refunded_item_id' 
				AND ref_oim1.meta_value = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta ref_oim2 ON ref_oim2.meta_key = '_line_total'
				AND ref_oim2.order_item_id = ref_oim1.order_item_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
		LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
		LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND ref_p.post_type = 'shop_order_refund'
			AND wclook.date_created > %s	
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}taste_order_transactions ot
				WHERE ot.order_item_id = wclook.order_item_id
					AND ot.trans_type = 'Refund'
			)
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$refunded_order_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($refunded_order_rows)) {
		echo 'No Refund orders found';
		return;
	}

	$prod_ids = array_unique(array_column($refunded_order_rows, 'product_id'));
	$prod_data = build_product_data($prod_ids);

	// build insert data 
	$rows_affected = insert_refunded_trans_rows($refunded_order_rows, $prod_data);

	if ($rows_affected) {
		echo $rows_affected, " refunded transaction rows inserted";
	} else {
		echo "Failure: ", $wpdb->last_error;
	}

	// insert and return  
	/*****  TODO:  Error Checking - need log file for errors */

}

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

	// var_dump($product_rows);

	return array_column($product_rows, null, 'post_id' );

}

function insert_order_trans_rows($new_order_rows, $prod_data) {
	global $wpdb;

	$trans_table = "{$wpdb->prefix}taste_order_transactions";
	$trans_type = 'Order';

	$sql = "
		INSERT INTO $trans_table
		(	order_id, order_item_id, trans_type, trans_amount, order_date, product_id,
			product_price, quantity, gross_revenue, venue_id, venue_name, creditor_id, 
			venue_creditor, coupon_id, coupon_code, coupon_value, net_cost, commission,
			vat, gross_income, venue_due )
		VALUES 
	";

	$prepare_values = array();

	foreach($new_order_rows as $order_info) {
		$product_id = $order_info['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$venue_id = $prod_data[$product_id]['venue_id'];
		$venue_name = $prod_data[$product_id]['venue_name'];
		$quantity = $order_info['item_qty'];
		$gross_revenue = $quantity * $product_price;
		$commission = ($gross_revenue / 100 ) * $product_comm;
		$vat = ($commission / 100) * $product_vat;
		/* round the amounts after calculating ...I think */
		$gross_revenue = round($gross_revenue, 2);
		$commission = round($commission, 2);
		$vat = round($vat, 2);
		$gross_income = $vat + $commission;
		$venue_due = $gross_revenue - $gross_income;
		$coupon_value = $order_info['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$creditor_id = $venue_id;
		$venue_creditor = $venue_name;

		// need to check for any coupon code's that match a previous order.  
		// Those are store credit coupons and orders created with them, get
		// a different transaction code:  "Order - From Credit"
		if (check_for_store_credit_coupon($order_info['coupon_codes']) ) {
			$trans_code = "Order - From Credit";
		} else {
			$trans_code = "Order";
		}

		/**  Not sure what the trans amount should be  */
		$trans_amount = $gross_revenue;

		$sql .= "(%d, %d, %s, %f, %s, %d, %f, %d, %f, %d, %s, %d,
			 %s, %s, %s, %f, %f, %f, %f, %f, %f),";

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], $trans_code, $trans_amount, 
			$order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, $venue_name,
			$creditor_id, $venue_creditor, $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, 
			$net_cost, $commission, $vat, $gross_income, $venue_due);

	}

	$sql = trim($sql, ',');
	
	$prepared_sql = $wpdb->prepare($sql, $prepare_values);
	$rows_affected = $wpdb->query($prepared_sql);

	return $rows_affected;

}

function check_for_store_credit_coupon($coupon_codes) {
	global $wpdb;

	$store_credit_flag = false;

	if (! $coupon_codes) {
		return $store_credit_flag;
	}

	// coupon codes are comma delimited listing
	$coupon_codes_array = explode(',', $coupon_codes);
	foreach($coupon_codes_array as $coupon_code) {
		if (is_numeric($coupon_code)) {
			$sql = "
				SELECT count(p.ID) as order_found 
					FROM wp_posts p
					WHERE p.ID = %d AND p.post_type = 'shop_order'
			";

			$order_check = $wpdb->get_results( 
											$wpdb->prepare($sql, $coupon_code), ARRAY_A);
			if ($order_check[0]['order_found']) {
				$store_credit_flag = true;
				break;
			}
		
		}
	}

	return $store_credit_flag;
}

function insert_redeemed_trans_rows($redeemed_order_rows, $prod_data) {
	global $wpdb;

	$trans_table = "{$wpdb->prefix}taste_order_transactions";
	$trans_type = 'Order';

	$sql = "
		INSERT INTO $trans_table
		(	order_id, order_item_id, trans_type, trans_amount, order_date, product_id,
			product_price, quantity, gross_revenue, venue_id, venue_name, creditor_id, 
			venue_creditor, coupon_id, coupon_code, coupon_value, net_cost, commission,
			vat, gross_income, venue_due )
		VALUES 
	";

	$prepare_values = array();

	foreach($redeemed_order_rows as $order_info) {
		$product_id = $order_info['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$venue_id = $prod_data[$product_id]['venue_id'];
		$venue_name = $prod_data[$product_id]['venue_name'];
		$quantity = $order_info['item_qty'];
		$gross_revenue = $quantity * $product_price;
		$commission = ($gross_revenue / 100 ) * $product_comm;
		$vat = ($commission / 100) * $product_vat;
		/* round the amounts after calculating ...I think */
		$gross_revenue = round($gross_revenue, 2);
		$commission = round($commission, 2);
		$vat = round($vat, 2);
		$gross_income = $vat + $commission;
		$venue_due = $gross_revenue - $gross_income;
		$coupon_value = $order_info['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$creditor_id = $venue_id;
		$venue_creditor = $venue_name;

		/**  Not sure what the trans amount should be  */
		$trans_amount = $gross_revenue;

		$sql .= "(%d, %d, %s, %f, %s, %d, %f, %d, %f, %d, %s, %d,
			 %s, %s, %s, %f, %f, %f, %f, %f, %f),";

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], 'Redemption', $trans_amount, 
			$order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, $venue_name,
			$creditor_id, $venue_creditor, $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, 
			$net_cost, $commission, $vat, $gross_income, $venue_due);

	}

	$sql = trim($sql, ',');
	
	$prepared_sql = $wpdb->prepare($sql, $prepare_values);
	$rows_affected = $wpdb->query($prepared_sql);

	return $rows_affected;

}

function insert_refunded_trans_rows($refunded_order_rows, $prod_data) {
	global $wpdb;

	$trans_table = "{$wpdb->prefix}taste_order_transactions";
	$trans_type = 'Refund';

	$sql = "
		INSERT INTO $trans_table
		(	order_id, order_item_id, trans_type, trans_amount, order_date, product_id,
			product_price, quantity, gross_revenue, venue_id, venue_name, creditor_id, 
			venue_creditor, refund_id, coupon_id, coupon_code, coupon_value, net_cost, commission,
			vat, gross_income, venue_due )
		VALUES 
	";

	$prepare_values = array();

	$prev_order_id = -999;
	$order_cnt = count($refunded_order_rows);

	for($key = 0; $key < $order_cnt; $key++) {
		$order_info = $refunded_order_rows[$key];
		// the basic order values are the same as any order
		// but determining the refund value will require more 
		// processing
		$order_id = $order_info['order_id'];
		$product_id = $order_info['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$venue_id = $prod_data[$product_id]['venue_id'];
		$venue_name = $prod_data[$product_id]['venue_name'];
		$quantity = $order_info['item_qty'];
		$gross_revenue = $quantity * $product_price;
		$commission = ($gross_revenue / 100 ) * $product_comm;
		$vat = ($commission / 100) * $product_vat;
		/* round the amounts after calculating ...I think */
		$gross_revenue = round($gross_revenue, 2);
		$commission = round($commission, 2);
		$vat = round($vat, 2);
		$gross_income = $vat + $commission;
		$venue_due = $gross_revenue - $gross_income;
		$coupon_value = $order_info['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$creditor_id = $venue_id;
		$venue_creditor = $venue_name;

		// to determine the refund amount (trans_amount) 
		// we need to test different refund scenarios

		if ($order_info['order_status'] === 'wc-refunded') {
			// easiest as all order items were fully refunded
			$trans_amount = $net_cost;
		} else {
			if ($prev_order_id !== $order_id) {
				// grab all rows for that order and then make decisions
				// and enter the refund distribution, if necessary, into 
				// the item_refund_amount for each item in the order
				$order_rows_with_refund_amts = calc_order_refund($refunded_order_rows, $order_info, $order_id, $prod_data, $key);

				$trans_amount = $order_rows_with_refund_amts[$key]['item_refund_amount'];
				// put any remaining order items w/ updated refund amount info back into the array
				foreach($order_rows_with_refund_amts as $upd_key => $upd_row) {
					$refunded_order_rows[$upd_key]['item_refund_amount'] = $upd_row['item_refund_amount'];
				}
				
				$prev_order_id = $order_id;
			} else {
				$trans_amount = $order_info['item_refund_amount'];
			}

		}

		if (!$trans_amount) {
			continue;
		}

		$sql .= "(%d, %d, %s, %f, %s, %d, %f, %d, %f, %d, %s, %d,
			 %s, %s, %s, %s, %f, %f, %f, %f, %f, %f),";

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], 'Refund', $trans_amount, 
			$order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, $venue_name,
			$creditor_id, $venue_creditor, $order_info['refund_ids'], $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, 
			$net_cost, $commission, $vat, $gross_income, $venue_due);

	}

	$sql = trim($sql, ',');
	
	$prepared_sql = $wpdb->prepare($sql, $prepare_values);
// $rows_affected = 0;
	$rows_affected = $wpdb->query($prepared_sql);

	return $rows_affected;

}

function calc_order_refund($refunded_order_rows, $order_info, $order_id, $prod_data, $key) {
	
	$order_array = array_filter($refunded_order_rows, function ($order_item_row) use ($order_id) {
		return $order_item_row['order_id'] === $order_id;
	} );

	// if only 1 order item, then use refund amount
	$item_cnt = count($order_array);

	if ($item_cnt === 1) {
		$order_array[$key]['item_refund_amount'] = $order_info['refund_total'];
		return $order_array;
	}

	if ($order_array[$key]['item_refund_amount'] === $order_array[$key]['refund_total']) {
		return $order_array;
	}


	// need to calculate net cost on each item - any item assigned refund
	// for each item in the order.  Also, get total of all item assigned refunds.
	// then, calculate remaining refund to distribute and loop through each order,
	// assigning available remaining amount until remaining refund = 0
	$total_refund = $order_array[$key]['refund_total'];
	$total_item_assigned_refund = 0;

	// first loop through to calc remaining refund possible for each item
	// as well as add to the appropriate totals
	foreach($order_array as &$order_item) {
		$product_id = $order_item['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$quantity = $order_item['item_qty'];
		$gross_revenue = $quantity * $product_price;
		$commission = ($gross_revenue / 100 ) * $product_comm;
		$vat = ($commission / 100) * $product_vat;
		/* round the amounts after calculating ...I think */
		$gross_revenue = round($gross_revenue, 2);
		$commission = round($commission, 2);
		$vat = round($vat, 2);
		$coupon_value = $order_item['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$item_refund_amount = $order_item['item_refund_amount'];
		$remaining_net_cost = $net_cost - $item_refund_amount;
		$total_item_assigned_refund += $item_refund_amount;
		$order_item['remaining_net_cost'] = $remaining_net_cost;	
		// convert any null refund amounts to 0...prevents trying to write out null later
		$order_item['item_refund_amount'] = $order_item['item_refund_amount'] ? $order_item['item_refund_amount'] : 0;
	}
	
	$remaining_refund_to_assign = $total_refund - $total_item_assigned_refund;

	// check to see if any item, which does not have an item amount assigned yet,
	// matches the remaining refund amount exactly.  If so, just match it up
	foreach($order_array as &$order_item) {
		if (! $order_item['item_refund_amount'] && $order_item['remaining_net_cost'] == $remaining_refund_to_assign) {
			$order_item['item_refund_amount'] = $remaining_refund_to_assign;
			$remaining_refund_to_assign = 0;
			break;
		}
	}

	if (! $remaining_refund_to_assign) {
		return $order_array;
	}

	// now loop through again and assign the remaining refunds to each item
	foreach($order_array as &$order_item) {
		if ($order_item['remaining_net_cost']) {
			$dist_amount = min($order_item['remaining_net_cost'], $remaining_refund_to_assign);
			$order_item['item_refund_amount'] += $dist_amount;
			$remaining_refund_to_assign -= $dist_amount;
			if (! $remaining_refund_to_assign) {
				break;
			}
		}
	}

	return $order_array;
}