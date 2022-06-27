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

}
add_action('taste_after_redeem', 'redeem_trans_rows_cm');

function redeem_trans_rows_mini($order_id) {

}
add_action('taste_after_redeem_mini', 'redeem_trans_rows_mini');