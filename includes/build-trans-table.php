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

	// process_refunds($start_date);

	// process_redemptions($start_date); 

	// process_payments($start_date);
	
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
		LEFT JOIN {$wpdb->prefix}taste_order_transactions ot ON ot.order_item_id = wclook.order_item_id
		LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
		LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
		WHERE op.post_status in ('wc-completed', 'wc-refunded', 'wc-on-hold')
			AND oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
			AND ot.trans_type IS NULL 
			AND wclook.date_created > %s
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$order_rows = $wpdb->get_results($wpdb->prepare($sql, $start_date), ARRAY_A);

	if (!count($order_rows)) {
		echo 'No orders found';
		die();
	}

 	// var_dump($order_rows);

	$prod_ids = array_unique(array_column($order_rows, 'product_id'));

	$prod_data = build_product_data($prod_ids);

	// var_dump($prod_data);

	// build insert data 
	$rows_affected = insert_order_trans_rows($order_rows, $prod_data);

	if ($rows_affected) {
		echo $rows_affected, " transaction rows inserted";
	} else {
		echo "Failure: ", $wpdb->last_error;
	}

	// insert and return  
	/*****  TODO:  Error Checking - need log file for errors */

	die();

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

function insert_order_trans_rows($order_rows, $prod_data) {
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

	foreach($order_rows as $order_info) {
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

		array_push( $prepare_values, $order_info['order_id'], $order_info['order_item_id'], 'Order', $trans_amount, 
			$order_info['order_date'], $product_id, $product_price, $quantity, $gross_revenue, $venue_id, $venue_name,
			$creditor_id, $venue_creditor, $order_info['coupon_ids'], $order_info['coupon_codes'], $coupon_value, 
			$net_cost, $commission, $vat, $gross_income, $venue_due);

	}

	$sql = trim($sql, ',');
	
	$prepared_sql = $wpdb->prepare($sql, $prepare_values);
	// echo $prepared_sql;
	// die();

	$rows_affected = $wpdb->query($prepared_sql);

	return $rows_affected;

}