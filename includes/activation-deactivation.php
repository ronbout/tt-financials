<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

/**
 *  ACTIVATION CODE 
 *  Add transactions table
 */


function tfinancial_add_transaction_table() {
	global $wpdb;
	$trans_table = $wpdb->prefix.'taste_order_transactions';

	$sql = "
	CREATE TABLE IF NOT EXISTS $trans_table (
		`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		`order_id` BIGINT(20) UNSIGNED NOT NULL,
		`order_item_id` BIGINT(20) UNSIGNED NOT NULL,
		`transaction_date` TIMESTAMP NULL DEFAULT NULL,
		`trans_type` ENUM('Order','Redemption','UnRedeem','Creditor Payment','Refund','Taste Credit','Order - From Credit','Redemption - From Credit','Bank Receipt') NOT NULL COLLATE 'latin1_swedish_ci',
		`trans_amount` DECIMAL(19,4) NULL DEFAULT NULL,
		`trans_entry_timestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
		`batch_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
		`batch_timestamp` TIMESTAMP NULL DEFAULT NULL,
		`order_date` DATETIME NOT NULL,
		`product_id` BIGINT(20) NOT NULL,
		`product_price` DECIMAL(19,4) UNSIGNED NOT NULL,
		`quantity` DECIMAL(19,4) NULL DEFAULT NULL,
		`gross_revenue` DECIMAL(19,4) NOT NULL,
		`customer_id` BIGINT(19) NULL DEFAULT NULL,
		`customer_name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
		`customer_email` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
		`venue_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
		`venue_name` VARCHAR(80) NULL DEFAULT NULL COLLATE 'latin1_swedish_ci',
		`creditor_id` BIGINT(20) NOT NULL,
		`venue_creditor` VARCHAR(80) NOT NULL COLLATE 'latin1_swedish_ci',
		`taste_credit_coupon_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
		`refund_id` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'latin1_swedish_ci',
		`coupon_id` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'latin1_swedish_ci',
		`coupon_code` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'latin1_swedish_ci',
		`coupon_value` DECIMAL(19,4) NULL DEFAULT NULL,
		`net_cost` DECIMAL(19,4) NOT NULL,
		`commission` DECIMAL(19,4) NOT NULL,
		`vat` DECIMAL(19,4) NOT NULL,
		`gross_income` DECIMAL(19,4) NOT NULL,
		`venue_due` DECIMAL(19,4) NOT NULL,,
		`payment_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
		`payment_status` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
		`payment_date` TIMESTAMP NULL DEFAULT NULL,
		`redemption_date` TIMESTAMP NULL DEFAULT NULL,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `order_id` (`order_id`) USING BTREE,
		INDEX `order_item_id` (`order_item_id`) USING BTREE,
		INDEX `trans_type` (`trans_type`) USING BTREE
	)";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}


function tfinancial_activation() {
	
	tfinancial_add_transaction_table();

}
/**** END OF ACTIVATION CODE ****/



/**
 * DEACTIVATION CODE
 */
function tfinancial_deactivation() {

	wp_clear_scheduled_hook( 'taste_nightly_event' );
	/**
	 *  *** NO!!!  DO NOT WANT TO LOSE INFO UNLESS SPECIFICALLY CHOSEN BY USER  ***
	 * remove table for one to many (venue to vouchers)
	 * 
	 */
}

/**** END OF DEACTIVATION CODE ****/