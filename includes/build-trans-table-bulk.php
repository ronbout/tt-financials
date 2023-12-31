<?php 
/**
 * 
 *  build-trans-table-bulk.php
 *  08/07/2021  Ron Boutilier
 * 
 *  build_trans_table_bulk function which will update the {$wpdb->prefix}taste_order_transactions
 *  table with any order transactions since the given date that have not already 
 *  been entered into the trans table.  This will most likely be run on a nightly
 *  basis.
 * 
 * 
 * 	TODO:  need log file for errors!!!
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

function build_trans_table_bulk($start_date = "2020-01-01", $output_to_file=false, $delete_flag=false) {

	if ($delete_flag) {
		// delete the entire trans table
		$delete_result = clear_trans_table();
		if (false === $delete_result) {
			echo "Could not delete order transaction rows";
			die;
		}
	}

	if ($output_to_file) {
		define('COMMENT_START', ' | ');
		define('COMMENT_END', ' | ');
	} else {
		define('COMMENT_START', '<h3>');
		define('COMMENT_END', '</h3>');
	}

	ob_start();

	process_new_orders($start_date);

	process_refunded_orders($start_date);

	process_taste_credit_orders($start_date);

	process_redeemed_orders($start_date); 

	process_paid_orders($start_date);

	$trans_build_output = ob_get_clean();

	if ($output_to_file) {
		$trans_build_output = date("Y-m-d H:i:s") . COMMENT_END . $trans_build_output . PHP_EOL;
		trans_build_log_write($trans_build_output);
	} else {
		echo $trans_build_output;
	}

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
			wclook.coupon_amount, op.post_date AS order_date,	wclook.customer_id,
			cust.first_name, cust.last_name, cust.email, pay_method.meta_value as ord_pay_method
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}postmeta pay_method on pay_method.post_id = wclook.order_id
				AND pay_method.meta_key = '_payment_method'
			LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
			LEFT JOIN {$wpdb->prefix}wc_order_product_lookup op_look ON op_look.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}wc_customer_lookup cust ON cust.customer_id = wclook.customer_id
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
		echo  COMMENT_START . 'No new orders found' . COMMENT_END;
		return;
	}
	$prod_ids = array_unique(array_column($new_order_rows, 'product_id'));
	$prod_data = build_product_data($prod_ids);

	// build insert data 
	$rows_affected = insert_new_order_trans_rows($new_order_rows, $prod_data);

	echo COMMENT_START;
	if ($rows_affected) {
		echo $rows_affected, " new order transaction rows inserted";
	} else {
		if ($wpdb->last_error) {
			echo "Failure inserting new order transaction rows: ", $wpdb->last_error;
		} else{
			echo "No new order transaction rows inserted";
		}
	}
	echo COMMENT_END;

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
			wclook.coupon_amount, op.post_date AS order_date,
			redaud.timestamp as redeem_date, wclook.customer_id,
			cust.first_name, cust.last_name, cust.email, pay_method.meta_value as ord_pay_method
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}postmeta pay_method on pay_method.post_id = wclook.order_id
				AND pay_method.meta_key = '_payment_method'
			LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
			LEFT JOIN {$wpdb->prefix}taste_venue_order_redemption_audit redaud ON 
				redaud.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}wc_customer_lookup cust ON cust.customer_id = wclook.customer_id
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND oi.downloaded = 1
			AND wclook.date_created > %s	
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}taste_order_transactions ot
				WHERE ot.order_item_id = wclook.order_item_id
					AND ot.trans_type IN ('Redemption', 'Redemption - From Credit')
			)
			AND (redaud.id = (
				SELECT MAX(redaud2.id)
				FROM wp_taste_venue_order_redemption_audit redaud2
				WHERE redaud2.order_item_id = wclook.order_item_id
			) OR redaud.id IS NULL)		
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$redeemed_order_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($redeemed_order_rows)) {
		echo COMMENT_START . 'No Redeemed orders found. ' . COMMENT_END;
		return;
	}

	$rows_affected = process_redeemed_order_list($redeemed_order_rows, 1);

	echo COMMENT_START;
	if ($rows_affected) {
		echo $rows_affected, " redeemed transaction rows inserted";
	} else {
		if ($wpdb->last_error) {
			echo "Failure inserting redeemed transaction rows: ", $wpdb->last_error;
		} else{
			echo "No redeemed transaction rows inserted";
		}
	}
	echo COMMENT_END;

}


function process_refunded_orders($start_date) {
	global $wpdb;

	// select orders with refunded amounts and NOT in trans table 
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
				AS item_refund_amount, MIN(ref_p.post_date) AS refund_date,	wclook.customer_id,
				cust.first_name, cust.last_name, cust.email, pay_method.meta_value as ord_pay_method
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}posts ref_p ON ref_p.post_parent = op.ID
			JOIN {$wpdb->prefix}postmeta ref_pm ON ref_pm.post_id = ref_p.ID AND ref_pm.meta_key = '_refund_amount'
			LEFT JOIN {$wpdb->prefix}postmeta pay_method on pay_method.post_id = wclook.order_id
				AND pay_method.meta_key = '_payment_method'
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta ref_oim1 ON ref_oim1.meta_key = '_refunded_item_id' 
				AND ref_oim1.meta_value = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta ref_oim2 ON ref_oim2.meta_key = '_line_total'
				AND ref_oim2.order_item_id = ref_oim1.order_item_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
		LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
		LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
		LEFT JOIN {$wpdb->prefix}wc_customer_lookup cust ON cust.customer_id = wclook.customer_id
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND ref_p.post_type = 'shop_order_refund'
			AND wclook.date_created > %s	
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}taste_order_transactions ot
				WHERE ot.order_id = wclook.order_id
					AND ot.trans_type = 'Refund'
			)
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$refunded_order_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($refunded_order_rows)) {
		echo COMMENT_START . 'No Refund orders found. ' . COMMENT_END;
		return;
	}

	$prod_ids = array_unique(array_column($refunded_order_rows, 'product_id'));
	$prod_data = build_product_data($prod_ids);

	// build insert data 
	$rows_affected = insert_refunded_trans_rows($refunded_order_rows, $prod_data);

	echo COMMENT_START;
	if ($rows_affected) {
		echo $rows_affected, " refunded transaction rows inserted";
	} else {
		if ($wpdb->last_error) {
			echo "Failure inserting refunded transaction rows: ", $wpdb->last_error;
		} else{
			echo "No refunded transaction rows inserted";
		}
	}
	echo COMMENT_END;

	// insert and return  
	/*****  TODO:  Error Checking - need log file for errors */

}


function process_taste_credit_orders($start_date) {
	global $wpdb;

	// select orders with store credit coupons pointing to them and NOT in trans table 
	$sql = "
		SELECT wclook.order_id, wclook.order_item_id, oim.meta_value AS item_qty, 
			wclook.product_id, oi.downloaded, op.post_status AS order_status,
			GROUP_CONCAT( cpn_look.coupon_id ) AS coupon_ids,
			GROUP_CONCAT( cpn_post.post_title ) AS coupon_codes,
			wclook.coupon_amount, op.post_date AS order_date,
			coupon_p.post_date as credit_date,	wclook.customer_id,
			cust.first_name, cust.last_name, cust.email, pay_method.meta_value as ord_pay_method
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}posts coupon_p ON coupon_p.post_title = wclook.order_id AND coupon_p.post_type = 'shop_coupon'
				AND coupon_p.post_status = 'publish'
			LEFT JOIN {$wpdb->prefix}postmeta pay_method on pay_method.post_id = wclook.order_id
				AND pay_method.meta_key = '_payment_method'
			LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
			LEFT JOIN {$wpdb->prefix}wc_customer_lookup cust ON cust.customer_id = wclook.customer_id
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND wclook.date_created > %s		
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}taste_order_transactions ot
				WHERE ot.order_id = wclook.order_id
					AND ot.trans_type  = 'Taste Credit'
			)	
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";	

		
//and wclook.order_id = 352826
		
	$taste_credit_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($taste_credit_rows)) {
		echo COMMENT_START . 'No Taste Credit orders found. ' . COMMENT_END;
		return;
	}
	$prod_ids = array_unique(array_column($taste_credit_rows, 'product_id'));
	$prod_data = build_product_data($prod_ids);

	// build insert data 
	$rows_affected = insert_taste_credit_trans_rows($taste_credit_rows, $prod_data);

	echo COMMENT_START;
	if ($rows_affected) {
		echo $rows_affected, " Taste Credit transaction rows inserted";
	} elseif ($wpdb->last_error) {
		echo "Failure: ", $wpdb->last_error;
	} else {
		echo 'No Taste Credit orders inserted';
	}
	echo COMMENT_END;

	// insert and return  
 // TODO:  Error Checking - need log file for errors 

}

function process_paid_orders($start_date) {
	global $wpdb;

	// select orders with paid order items data and NOT in trans table 
	$sql = "
		SELECT wclook.order_id, wclook.order_item_id, oim.meta_value AS item_qty, 
			wclook.product_id, oi.downloaded, op.post_status AS order_status,
			GROUP_CONCAT( cpn_look.coupon_id ) AS coupon_ids,
			GROUP_CONCAT( cpn_post.post_title ) AS coupon_codes,
			wclook.coupon_amount, op.post_date AS order_date, wclook.customer_id,
			pay.id AS payment_id, pay.payment_date, pay.status AS payment_status,
			cust.first_name, cust.last_name, cust.email, pay_method.meta_value as ord_pay_method
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}postmeta pay_method on pay_method.post_id = wclook.order_id
				AND pay_method.meta_key = '_payment_method'
			LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
			LEFT JOIN {$wpdb->prefix}wc_customer_lookup cust ON cust.customer_id = wclook.customer_id
			JOIN {$wpdb->prefix}taste_venue_payment_order_item_xref poix ON poix.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}taste_venue_payment pay ON pay.id = poix.payment_id AND pay.status <> 2
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND wclook.date_created > %s	
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}taste_order_transactions ot
				WHERE ot.order_item_id = wclook.order_item_id
					AND ot.trans_type = 'Creditor Payment'
			)
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$paid_order_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($paid_order_rows)) {
		echo COMMENT_START . 'No Paid orders found. ' . COMMENT_END;
		return;
	}
	
	$rows_affected = process_paid_order_list($paid_order_rows);

	echo COMMENT_START;
	if ($rows_affected) {
		echo $rows_affected, " paid transaction rows inserted";
	} else {
		if ($wpdb->last_error) {
			echo "Failure inserting paid transaction rows: ", $wpdb->last_error;
		} else{
			echo "No paid transaction rows inserted";
		}
	}
	echo COMMENT_END;

	// insert and return  
	/*****  TODO:  Error Checking - need log file for errors */

}

function insert_new_order_trans_rows($new_order_rows, $prod_data) {
	global $wpdb;

	$trans_table = "{$wpdb->prefix}taste_order_transactions";

	$sql = "
		INSERT INTO $trans_table
		(	order_id, order_item_id, transaction_date, trans_type, trans_amount, order_date, product_id,
			product_price, quantity, gross_revenue, venue_id, venue_name, creditor_id, 
			venue_creditor, coupon_id, coupon_code, coupon_value, net_cost, commission,
			vat, gross_income, venue_due, customer_id, customer_name, customer_email, order_payment_method )
		VALUES 
	";

	$prepare_values = array();

	$prev_order_id = -999;
	$order_cnt = count($new_order_rows);

	for($key = 0; $key < $order_cnt; $key++) {
		$order_info = $new_order_rows[$key];
		$order_id = $order_info['order_id'];
		$product_id = $order_info['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$venue_id = $prod_data[$product_id]['venue_id'];
		$venue_name = $prod_data[$product_id]['venue_name'];
		$creditor_id = $prod_data[$product_id]['creditor_id'];
		$creditor_name = $prod_data[$product_id]['creditor_name'];
		$quantity = $order_info['item_qty'];
		$customer_id = $order_info['customer_id'];
		$customer_name = $order_info['last_name'] . ", " . $order_info['first_name'];
		$customer_email = $order_info['email'];
		$order_payment_method = $order_info['ord_pay_method'];
		
		$curr_prod_values = tf_calc_net_payable($product_price, $product_vat, $product_comm, $quantity, true);
		$gross_revenue = $curr_prod_values['gross_revenue'];
		$commission = $curr_prod_values['commission'];
		$vat = $curr_prod_values['vat'];
		$venue_due = $curr_prod_values['net_payable'];

		$gross_income = $vat + $commission;
		$coupon_value = $order_info['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$creditor_id = $creditor_id ? $creditor_id : $venue_id;
		$venue_creditor = $creditor_name ? $creditor_name : $venue_name;

		// need to check for any coupon code's that match a previous order.  
		// Those are store credit coupons and orders created with them, get
		// a different transaction code:  "Order - From Credit"
		// and the store credit amount goes in the trans_amount field

		if ($prev_order_id !== $order_id) {
			if ($order_info['coupon_ids']) {
				$order_rows_with_credit_amts = check_for_store_credit_coupon($new_order_rows, $order_info, $order_id, $prod_data);

				// put order items w/ updated store credit amount info back into the array
				// and update the current order info
				$order_info['store_credit_amount'] = $order_rows_with_credit_amts[$key]['store_credit_amount'];
				foreach($order_rows_with_credit_amts as $upd_key => $upd_row) {
					$new_order_rows[$upd_key]['store_credit_amount'] = $upd_row['store_credit_amount'];
				}
			}
			
			$prev_order_id = $order_id;
		}

		if (isset($order_info['store_credit_amount']) && $order_info['store_credit_amount'] ) {
			$trans_code = "Order - From Credit";
			$trans_amount = $order_info['store_credit_amount'];
		} else {
			$trans_code = "Order";
			$trans_amount = $gross_revenue;
		}


		$sql .= "(%d, %d, %s, %s, %f, %s, %d, %f, %d, %f, %d, %s, %d,
			 %s, %s, %s, %f, %f, %f, %f, %f, %f, %d, %s, %s, %s),";

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], $order_info['order_date'],
			 $trans_code, $trans_amount, $order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, $venue_name,	$creditor_id, $venue_creditor, $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, $net_cost, $commission, $vat, $gross_income, $venue_due, $customer_id, $customer_name, $customer_email, $order_payment_method);

	}

	$sql = trim($sql, ',');
	
	$prepared_sql = $wpdb->prepare($sql, $prepare_values);
	$prepared_sql = str_replace("''",'NULL', $prepared_sql);
	$rows_affected = $wpdb->query($prepared_sql);

	return $rows_affected;

}

function check_for_store_credit_coupon($new_order_rows, $order_info, $order_id, $prod_data) {
	global $wpdb;

	$store_credit_count = 0;
	$total_credit_amount = 0;

	$coupon_ids = $order_info['coupon_ids'];
	$coupon_codes = $order_info['coupon_codes'];

	$order_array = array_filter($new_order_rows, function ($order_item_row) use ($order_id) {
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
			// loop through order items once to get total gross revenue
			// Note: the order total gross revenue has proven unreliable
			$total_gross_revenue = 0;
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
				$total_gross_revenue += $gross_revenue;
			}
			// now loop through each item, assigning the appropriate
			// % of total store credit to each item
			foreach($order_array as &$order_item) {
				$order_item['store_credit_amount'] = ($order_item['gross_revenue'] / $total_gross_revenue) * $total_credit_amount;
			}
		}
	}
	return $order_array;
}

function insert_refunded_trans_rows($refunded_order_rows, $prod_data) {
	global $wpdb;

	$trans_table = "{$wpdb->prefix}taste_order_transactions";

	$sql = "
		INSERT INTO $trans_table
		(	order_id, order_item_id, transaction_date, trans_type, trans_amount, order_date, product_id,
			product_price, quantity, gross_revenue, venue_id, venue_name, creditor_id, 
			venue_creditor, refund_id, coupon_id, coupon_code, coupon_value, net_cost, commission,
			vat, gross_income, venue_due, customer_id, customer_name, customer_email, order_payment_method )
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
		$creditor_id = $prod_data[$product_id]['creditor_id'];
		$creditor_name = $prod_data[$product_id]['creditor_name'];
		$quantity = $order_info['item_qty'];
		$customer_id = $order_info['customer_id'];
		$customer_name = $order_info['last_name'] . ", " . $order_info['first_name'];
		$customer_email = $order_info['email'];
		$order_payment_method = $order_info['ord_pay_method'];

		$curr_prod_values = tf_calc_net_payable($product_price, $product_vat, $product_comm, $quantity, true);
		$gross_revenue = $curr_prod_values['gross_revenue'];
		$commission = $curr_prod_values['commission'];
		$vat = $curr_prod_values['vat'];
		$venue_due = $curr_prod_values['net_payable'];

		$gross_income = $vat + $commission;
		$coupon_value = $order_info['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$creditor_id = $creditor_id ? $creditor_id : $venue_id;
		$venue_creditor = $creditor_name ? $creditor_name : $venue_name;

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

		$sql .= "(%d, %d, %s, %s, %f, %s, %d, %f, %d, %f, %d, %s, %d,
			 %s, %s, %s, %s, %f, %f, %f, %f, %f, %f, %d, %s, %s, %s),";

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], $order_info['refund_date'],
			'Refund', $trans_amount, $order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, $venue_name,	$creditor_id, $venue_creditor, $order_info['refund_ids'], $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, $net_cost, $commission, $vat, $gross_income, $venue_due, $customer_id, $customer_name, $customer_email, $order_payment_method);

	}

	$sql = trim($sql, ',');
	
	if (count($prepare_values)) {
		$prepared_sql = $wpdb->prepare($sql, $prepare_values);
		$prepared_sql = str_replace("''",'NULL', $prepared_sql);
		$rows_affected = $wpdb->query($prepared_sql);
	} else {
		$rows_affected = 0;
	}
	
	return $rows_affected;

}


function calc_order_refund($refunded_order_rows, $order_info, $order_id, $prod_data, $key) {

	$refund_amount = $order_info['refund_total'];
	
	$order_array = array_filter($refunded_order_rows, function ($order_item_row) use ($order_id) {
		return $order_item_row['order_id'] === $order_id;
	} );

	// if only 1 order item, then use refund amount
	$item_cnt = count($order_array);

	if ($item_cnt === 1) {
		$order_array[$key]['item_refund_amount'] = $refund_amount;
		return $order_array;
	}

	if ($order_array[$key]['item_refund_amount'] === $refund_amount) {
		return $order_array;
	}

	// need to calculate net cost on each item - any item assigned refund
	// for each item in the order.  Also, get total of all item assigned refunds.
	// then, calculate remaining refund to distribute and loop through each order,
	// assigning available remaining amount until remaining refund = 0
	$total_item_assigned_refund = 0;
	$total_order_amt = 0;
	$total_order_gross = 0;

	// first loop through to calc remaining refund possible for each item
	// as well as add to the appropriate totals
	foreach($order_array as &$order_item) {
		$product_id = $order_item['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$quantity = $order_item['item_qty'];

		$curr_prod_values = tf_calc_net_payable($product_price, $product_vat, $product_comm, $quantity, true);
		$gross_revenue = $curr_prod_values['gross_revenue'];
		$commission = $curr_prod_values['commission'];
		$vat = $curr_prod_values['vat'];
		$venue_due = $curr_prod_values['net_payable'];
		$gross_income = $vat + $commission;

		$coupon_value = $order_item['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$item_refund_amount = $order_item['item_refund_amount'];
		$remaining_net_cost = $net_cost - $item_refund_amount;
		$total_item_assigned_refund += $item_refund_amount;
		$order_item['remaining_net_cost'] = $remaining_net_cost;
		$order_item['gross_cost'] = $gross_revenue;		
		$total_order_amt += $net_cost;
		$total_order_gross += $gross_revenue;
		// convert any null refund amounts to 0...prevents trying to write out null later
		$order_item['item_refund_amount'] = $order_item['item_refund_amount'] ? $order_item['item_refund_amount'] : 0;
	}
	
	$remaining_refund_to_assign = $refund_amount - $total_item_assigned_refund;
	
	// if total order amount = refund amount, each item refund is its net cost
	if ($refund_amount == $total_order_amt) {
		foreach($order_array as &$order_item) {
			$order_item['item_refund_amount'] = $order_item['net_cost'];
		}
		return $order_array;
	}

	// if gross revenue = refund amount, the coupon was accounted for
	// and each item refund is its gross cost
	if ($refund_amount == $total_order_gross) {
		foreach($order_array as &$order_item) {
			$order_item['item_refund_amount'] = $order_item['gross_cost'];
		}
		return $order_array;
	}

	// check to see if any item, which does not have an item amount assigned yet,
	// matches the remaining refund amount exactly.  If so, just match it up
	foreach($order_array as &$order_item) {
		if (1 == $order_item['downloaded']) {
			continue;
		}
		if (! $order_item['item_refund_amount'] && ($order_item['remaining_net_cost'] == $remaining_refund_to_assign || $order_item['gross_cost'] == $remaining_refund_to_assign)) {
			$order_item['item_refund_amount'] = $remaining_refund_to_assign;
			$remaining_refund_to_assign = 0;
			break;
		}
	}

	if (! $remaining_refund_to_assign) {
		return $order_array;
	}

	// same as above, only assign even if downloaded
	foreach($order_array as &$order_item) {
		if (! $order_item['item_refund_amount'] && ($order_item['remaining_net_cost'] == $remaining_refund_to_assign || $order_item['gross_cost'] == $remaining_refund_to_assign)) {
			$order_item['item_refund_amount'] = $remaining_refund_to_assign;
			$remaining_refund_to_assign = 0;
			break;
		}
	}

	if (! $remaining_refund_to_assign) {
		return $order_array;
	}

	if (3 === count($order_array)) {
		$calc_fields = array('remaining_net_cost', 'gross_cost');
		$item_keys = array_keys($order_array);

		foreach ($calc_fields as $calc_field) {
			if ($order_array[$item_keys[0]][$calc_field] + $order_array[$item_keys[1]][$calc_field] == $remaining_refund_to_assign) {
				$order_array[$item_keys[0]]['item_refund_amount'] = $order_array[$item_keys[0]][$calc_field];
				$order_array[$item_keys[1]]['item_refund_amount'] = $order_array[$item_keys[1]][$calc_field];
				$remaining_refund_to_assign = 0;
				break;
			}
			if ($order_array[$item_keys[0]][$calc_field] + $order_array[$item_keys[2]][$calc_field] == $remaining_refund_to_assign) {
				$order_array[$item_keys[0]]['item_refund_amount'] = $order_array[$item_keys[0]][$calc_field];
				$order_array[$item_keys[2]]['item_refund_amount'] = $order_array[$item_keys[2]][$calc_field];
				$remaining_refund_to_assign = 0;
				break;
			}
			if ($order_array[$item_keys[1]][$calc_field] + $order_array[$item_keys[2]][$calc_field] == $remaining_refund_to_assign) {
				$order_array[$item_keys[1]]['item_refund_amount'] = $order_array[$item_keys[1]][$calc_field];
				$order_array[$item_keys[2]]['item_refund_amount'] = $order_array[$item_keys[2]][$calc_field];
				$remaining_refund_to_assign = 0;
				break;
			}
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


function insert_taste_credit_trans_rows($taste_credit_rows, $prod_data) {
	global $wpdb;

	$trans_table = "{$wpdb->prefix}taste_order_transactions";

	$sql = "
		INSERT INTO $trans_table
		(	order_id, order_item_id, transaction_date, trans_type, trans_amount, order_date, product_id,
			product_price, quantity, gross_revenue, venue_id, venue_name, creditor_id, 
			venue_creditor, taste_credit_coupon_id, coupon_id, coupon_code, coupon_value, net_cost, commission,
			vat, gross_income, venue_due, customer_id, customer_name, customer_email, order_payment_method )
		VALUES 
	";

	$prepare_values = array();

	$prev_order_id = -999;
	$order_cnt = count($taste_credit_rows);
		
	for($key = 0; $key < $order_cnt; $key++) {
		$order_info = $taste_credit_rows[$key];
		// the basic order values are the same as any order
		// but determining the store credit value will require more 
		// processing
		$order_id = $order_info['order_id'];
		$product_id = $order_info['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$venue_id = $prod_data[$product_id]['venue_id'];
		$venue_name = $prod_data[$product_id]['venue_name'];
		$creditor_id = $prod_data[$product_id]['creditor_id'];
		$creditor_name = $prod_data[$product_id]['creditor_name'];
		$quantity = $order_info['item_qty'];
		$customer_id = $order_info['customer_id'];
		$customer_name = $order_info['last_name'] . ", " . $order_info['first_name'];
		$customer_email = $order_info['email'];
		$order_payment_method = $order_info['ord_pay_method'];

		$curr_prod_values = tf_calc_net_payable($product_price, $product_vat, $product_comm, $quantity, true);
		$gross_revenue = $curr_prod_values['gross_revenue'];
		$commission = $curr_prod_values['commission'];
		$vat = $curr_prod_values['vat'];
		$venue_due = $curr_prod_values['net_payable'];

		$gross_income = $vat + $commission;
		$coupon_value = $order_info['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$creditor_id = $creditor_id ? $creditor_id : $venue_id;
		$venue_creditor = $creditor_name ? $creditor_name : $venue_name;

		// to determine the store credi amount (trans_amount) 
		// we need to retrieve the coupon and test different 
		// scenarios as they relate to which item(s) were credited	
		$credit_coupon_info = get_credit_coupon_info($order_id);

		$credit_amount = $credit_coupon_info[0]['coupon_amount'];
		$credit_coupon_id = $credit_coupon_info[0]['ID'];

		if ($prev_order_id !== $order_id) {
			// grab all rows for that order and then make decisions
			// and enter the credit distribution, if necessary, into 
			// the item_credit_amount for each item in the order
			$order_rows_with_credit_amts = calc_order_credit($taste_credit_rows, $order_info, $order_id, $prod_data, $key, $credit_amount);

			$trans_amount = $order_rows_with_credit_amts[$key]['item_credit_amount'];
			// put any remaining order items w/ updated credit amount info back into the array
			foreach($order_rows_with_credit_amts as $upd_key => $upd_row) {
				$taste_credit_rows[$upd_key]['item_credit_amount'] = $upd_row['item_credit_amount'];
			}
			
			$prev_order_id = $order_id;
		} else {
			$trans_amount = $order_info['item_credit_amount'];
		}

		if (!$trans_amount) {
			continue;
		}

		$sql .= "(%d, %d, %s, %s, %f, %s, %d, %f, %d, %f, %d, %s, %d,
			 %s, %d, %s, %s, %f, %f, %f, %f, %f, %f, %d, %s, %s, %s),";

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], $order_info['credit_date'],
			'Taste Credit', $trans_amount, 
			$order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, $venue_name,
			$creditor_id, $venue_creditor, $credit_coupon_id, $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, 
			$net_cost, $commission, $vat, $gross_income, $venue_due, $customer_id, $customer_name, $customer_email, $order_payment_method);

	}

	$sql = trim($sql, ',');

	if (count($prepare_values)) {
		$prepared_sql = $wpdb->prepare($sql, $prepare_values);	
		$prepared_sql = str_replace("''",'NULL', $prepared_sql);
		$rows_affected = $wpdb->query($prepared_sql);
	} else {
		$rows_affected = 0;
	}

	return $rows_affected;

}

function get_credit_coupon_info($order_id) {
	global $wpdb;

	$sql = "
		SELECT coup_p.*, coup_pm.meta_value AS coupon_amount
		FROM wp_posts coup_p
		JOIN wp_postmeta coup_pm ON coup_pm.post_id = coup_p.ID
			AND coup_pm.meta_key = 'coupon_amount'
		WHERE coup_p.post_title = %s
			AND coup_p.post_type = 'shop_coupon'
			AND coup_p.post_status = 'publish'
		ORDER BY coup_p.post_date
		LIMIT 1
	";

	
	$coupon_info = $wpdb->get_results( 
		$wpdb->prepare($sql, $order_id), ARRAY_A);

	return $coupon_info;
}


function calc_order_credit($order_rows, $order_info, $order_id, $prod_data, $key, $credit_amount) {
	
	$order_array = array_filter($order_rows, function ($order_item_row) use ($order_id) {
		return $order_item_row['order_id'] === $order_id;
	} );

	// if only 1 order item, then use credit amount
	$item_cnt = count($order_array);

	if ($item_cnt === 1) {
		$order_array[$key]['item_credit_amount'] = $credit_amount;
		return $order_array;
	}

	// need to calculate net cost on each item - then distribute the 
	// store credit amount as best as possible
	$total_item_assigned_credit = 0;
	$total_order_amt = 0;
	$total_order_gross = 0;

	// first loop through to calc remaining credit possible for each item
	// as well as add to the appropriate totals
	foreach($order_array as &$order_item) {
		$product_id = $order_item['product_id'];
		$product_price = $prod_data[$product_id]['price'];
		$product_comm = $prod_data[$product_id]['commission'];
		$product_vat = $prod_data[$product_id]['vat'];
		$quantity = $order_item['item_qty'];

		$curr_prod_values = tf_calc_net_payable($product_price, $product_vat, $product_comm, $quantity, true);
		$gross_revenue = $curr_prod_values['gross_revenue'];
		$commission = $curr_prod_values['commission'];
		$vat = $curr_prod_values['vat'];
		$venue_due = $curr_prod_values['net_payable'];
		$gross_income = $vat + $commission;

		$coupon_value = $order_item['coupon_amount'];
		$net_cost = $gross_revenue - $coupon_value;
		$order_item['net_cost'] = $net_cost;	
		$order_item['gross_cost'] = $gross_revenue;	
		$order_item['item_credit_amount'] = 0;
		$total_order_amt += $net_cost;
		$total_order_gross += $gross_revenue;
	}
	
	$remaining_credit_to_assign = $credit_amount;

	// if total order amount = credit amount, each item credit is its net cost
	if ($credit_amount == $total_order_amt) {
		foreach($order_array as &$order_item) {
			$order_item['item_credit_amount'] = $order_item['net_cost'];
		}
		return $order_array;
	}

	// if gross revenue = credit amount, the coupon was accounted for
	// and each item credit is its gross cost
	if ($credit_amount == $total_order_gross) {
		foreach($order_array as &$order_item) {
			$order_item['item_credit_amount'] = $order_item['gross_cost'];
		}
		return $order_array;
	}

	// check to see if any item cost matches the credit amount exactly.  
	// If so, just match it up.  then, check item gross.  
	// for this go around, do NOT assign if item is downloadd
	foreach($order_array as &$order_item) {
		if (1 == $order_item['downloaded']) {
			continue;
		}
		if ( $order_item['net_cost'] == $remaining_credit_to_assign || 
					$order_item['gross_cost'] == $remaining_credit_to_assign) {
			$order_item['item_credit_amount'] = $remaining_credit_to_assign;
			$remaining_credit_to_assign = 0;
			break;
		}
	}

	if (! $remaining_credit_to_assign) {
		return $order_array;
	}

	// same as above, only assign even if downloaded
	foreach($order_array as &$order_item) {
		if ( $order_item['net_cost'] == $remaining_credit_to_assign || 
					$order_item['gross_cost'] == $remaining_credit_to_assign) {
			$order_item['item_credit_amount'] = $remaining_credit_to_assign;
			$remaining_credit_to_assign = 0;
			break;
		}
	}

	if (! $remaining_credit_to_assign) {
		return $order_array;
	}

	if (3 === count($order_array)) {
		$calc_fields = array('net_cost', 'gross_cost');
		$item_keys = array_keys($order_array);

		foreach ($calc_fields as $calc_field) {
			if ($order_array[$item_keys[0]][$calc_field] + $order_array[$item_keys[1]][$calc_field] == $remaining_credit_to_assign) {
				$order_array[$item_keys[0]]['item_credit_amount'] = $order_array[$item_keys[0]][$calc_field];
				$order_array[$item_keys[1]]['item_credit_amount'] = $order_array[$item_keys[1]][$calc_field];
				$remaining_credit_to_assign = 0;
				break;
			}
			if ($order_array[$item_keys[0]][$calc_field] + $order_array[$item_keys[2]][$calc_field] == $remaining_credit_to_assign) {
				$order_array[$item_keys[0]]['item_credit_amount'] = $order_array[$item_keys[0]][$calc_field];
				$order_array[$item_keys[2]]['item_credit_amount'] = $order_array[$item_keys[2]][$calc_field];
				$remaining_credit_to_assign = 0;
				break;
			}
			if ($order_array[$item_keys[1]][$calc_field] + $order_array[$item_keys[2]][$calc_field] == $remaining_credit_to_assign) {
				$order_array[$item_keys[1]]['item_credit_amount'] = $order_array[$item_keys[1]][$calc_field];
				$order_array[$item_keys[2]]['item_credit_amount'] = $order_array[$item_keys[2]][$calc_field];
				$remaining_credit_to_assign = 0;
				break;
			}
		}
	}
	
	if (! $remaining_credit_to_assign) {
		return $order_array;
	}

	// now loop through again and assign the remaining credit to each item
	foreach($order_array as &$order_item) {
		if ($order_item['net_cost']) {
			$dist_amount = min(1.2 * $order_item['net_cost'], $remaining_credit_to_assign);
			$order_item['item_credit_amount'] += $dist_amount;
			$remaining_credit_to_assign -= $dist_amount;
			if (! $remaining_credit_to_assign) {
				break;
			}
		}
	}

	return $order_array;
}

function clear_trans_table() {
	global $wpdb;

	$sql = "
		DELETE FROM {$wpdb->prefix}taste_order_transactions
		WHERE id > 0
	";

	$results = $wpdb->query($sql);
	return $results;
}
