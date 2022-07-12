<?php
/**
 * 
 *  real-time-trans-build.php
 * 
 *  Code to hook into various actions that will write out
 *  transaction rows, so that it is updated in real time
 * 
 *  06/27/2022
 *  Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

/***********************************************************
*   Functions for Redeemed / UnRedeemed transaction types
************************************************************/

function redeem_trans_rows_cm($order_list, $redeem_flg) {

  $order_item_ids = array_column($order_list, 'orderItemId');
  $redeemed_order_rows = retrieve_redeem_order_info($order_item_ids);

  $formatted_date = date('Y-m-d H:i:s');

  $rows_affected = process_redeemed_order_list($redeemed_order_rows, $redeem_flg, $formatted_date);
}
add_action('taste_after_redeem', 'redeem_trans_rows_cm', 10, 2);

function redeem_trans_rows_mini($order_item_id) {
  $redeem_flg = 1;
  $item_id_array = array($order_item_id);
  $redeemed_order_rows = retrieve_redeem_order_info($item_id_array);
  
  $formatted_date = date('Y-m-d H:i:s');
  $rows_affected = process_redeemed_order_list($redeemed_order_rows, $redeem_flg, $formatted_date);
}
add_action('taste_after_redeem_mini', 'redeem_trans_rows_mini');

function retrieve_redeem_order_info($order_item_list) {
	global $wpdb;
  
	$placeholders = array_fill(0, count($order_item_list), '%d');
	$placeholders = implode(', ', $placeholders);

  $sql = "
    SELECT wclook.order_id, wclook.order_item_id, oim.meta_value AS item_qty, 
      wclook.product_id, oi.downloaded, op.post_status AS order_status,
      GROUP_CONCAT( cpn_look.coupon_id ) AS coupon_ids,
      GROUP_CONCAT( cpn_post.post_title ) AS coupon_codes,
      wclook.coupon_amount, op.post_date AS order_date, wclook.customer_id,
			cust.first_name, cust.last_name, cust.email
    FROM {$wpdb->prefix}wc_order_product_lookup wclook
      JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
      JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
      JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
      LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
      LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
      LEFT JOIN {$wpdb->prefix}wc_customer_lookup cust ON cust.customer_id = wclook.customer_id
    WHERE oim.meta_key = '_qty'
      AND op.post_type = 'shop_order'	
      AND wclook.order_item_id IN ($placeholders)
    GROUP BY wclook.order_item_id
    ORDER BY op.post_date DESC";

	$refunded_order_rows = $wpdb->get_results($wpdb->prepare($sql, $order_item_list), ARRAY_A);

  return $refunded_order_rows;
}
/***********************************************************
*   End of Redeemed / UnRedeemed transaction types
************************************************************/


/***********************************************************
*   Functions for Creditor Payment transaction types
************************************************************/

function payment_trans_rows_cm($payment_id, $payment_info) {
  $edit_mode = $payment_info['edit_mode'];
  $order_item_ids = $payment_info['order_item_ids'];
  $payment_status = $payment_info['payment_status'];
  $payment_date =  $payment_info['payment_date'];

  switch($edit_mode) {
    case 'INSERT':
      if (TASTE_PAYMENT_STATUS_ADJ != $payment_status) {
        $paid_order_rows = retrieve_paid_order_info($order_item_ids);
        $rows_affected = process_paid_order_list($paid_order_rows, $payment_date, $payment_id, $payment_status);
      }
      break;
    case 'DELETE':
      delete_paid_order_rows($payment_id);
      break;
    case 'UPDATE':
      delete_paid_order_rows($payment_id);
      if (TASTE_PAYMENT_STATUS_ADJ != $payment_status) {
        $paid_order_rows = retrieve_paid_order_info($order_item_ids);
        $rows_affected = process_paid_order_list($paid_order_rows, $payment_date, $payment_id, $payment_status);
      }
  }

}
add_action('taste_payment_update', 'payment_trans_rows_cm', 10, 2);

function retrieve_paid_order_info($order_item_list) {
	global $wpdb;
  
	$placeholders = array_fill(0, count($order_item_list), '%d');
	$placeholders = implode(', ', $placeholders);

    $sql = "
		SELECT wclook.order_id, wclook.order_item_id, oim.meta_value AS item_qty, 
			wclook.product_id, oi.downloaded, op.post_status AS order_status,
			GROUP_CONCAT( cpn_look.coupon_id ) AS coupon_ids,
			GROUP_CONCAT( cpn_post.post_title ) AS coupon_codes,
			wclook.coupon_amount, op.post_date AS order_date, wclook.customer_id,
			cust.first_name, cust.last_name, cust.email
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}posts op ON op.ID = wclook.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = wclook.order_item_id
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}wc_order_coupon_lookup cpn_look ON cpn_look.order_id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}posts cpn_post ON cpn_post.ID = cpn_look.coupon_id
      LEFT JOIN {$wpdb->prefix}wc_customer_lookup cust ON cust.customer_id = wclook.customer_id
		WHERE oim.meta_key = '_qty'
			AND op.post_type = 'shop_order'
      AND wclook.order_item_id IN ($placeholders)
		GROUP BY wclook.order_item_id
		ORDER BY op.post_date DESC";

	$paid_order_rows = $wpdb->get_results($wpdb->prepare($sql, $order_item_list), ARRAY_A);

  return $paid_order_rows;
}

function delete_paid_order_rows($payment_id) {
	global $wpdb;

  $sql = "
    DELETE FROM {$wpdb->prefix}taste_order_transactions o_trans
    WHERE o_trans.trans_type = 'Creditor Payment'
      AND o_trans.payment_id = %d
  ";
  $rows_affected = $wpdb->query($wpdb->prepare($sql, $payment_id));
  return $rows_affected;

}

/***********************************************************
*   End of Creditor Payment transaction types
************************************************************/

