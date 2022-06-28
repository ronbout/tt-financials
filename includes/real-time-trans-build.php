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

function redeem_trans_rows_cm($order_list, $redeem_flg) {

  $order_item_ids = array_column($order_list, 'orderItemId');
  $redeemed_order_rows = retrieve_redeem_order_info($order_item_ids);

  $formatted_date = date('Y-m-d H:i:s');

  $rows_affected = process_redeemed_order_list($redeemed_order_rows, $redeem_flg, $formatted_date);

  /**
   * should write to some log file
   * 
   */
}
add_action('taste_after_redeem', 'redeem_trans_rows_cm', 10, 2);

// function redeem_trans_rows_mini($order_item_id) {

// }
// add_action('taste_after_redeem_mini', 'redeem_trans_rows_mini');

function retrieve_redeem_order_info($order_item_list) {
	global $wpdb;
  
	$placeholders = array_fill(0, count($order_item_list), '%d');
	$placeholders = implode(', ', $placeholders);

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
    WHERE oim.meta_key = '_qty'
      AND op.post_type = 'shop_order'	
      AND wclook.order_item_id IN ($placeholders)
    GROUP BY wclook.order_item_id
    ORDER BY op.post_date DESC";

	$refunded_order_rows = $wpdb->get_results($wpdb->prepare($sql, $order_item_list), ARRAY_A);

  return $refunded_order_rows;
}
