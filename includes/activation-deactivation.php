<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

/**
 *  ACTIVATION CODE 
 *  Add transactions table
 */


function tfinancial_add_transaction_table() {
	global $wpdb;
	$venue_table = $wpdb->prefix.'taste_order_transaction';

	// $sql = "CREATE TABLE IF NOT EXISTS $venue_table (
	// 		venue_id BIGINT(20) UNSIGNED NOT NULL,
	// 		name VARCHAR(80) NOT NULL,
	// 		description VARCHAR(255),
	// 		address1 VARCHAR(120),
	// 		address2 VARCHAR(120),
	// 		city VARCHAR(100),
	// 		postcode VARCHAR(20),
	// 		country VARCHAR(100),
	// 		state VARCHAR(100),
	// 		phone VARCHAR(40),
	// 		venue_type ENUM ('Restaurant', 'Bar', 'Hotel', 'Product'),
	// 		voucher_pct FLOAT,
	// 		paid_member TINYINT(1) ZEROFILL NOT NULL DEFAULT 0, 
	// 		member_renewal_date DATE,
	// 		membership_cost DECIMAL(10,2),
	// 		PRIMARY KEY (venue_id),
	// 		UNIQUE KEY (name),
	// 		KEY (venue_type)
	// 	)";

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

	/**
	 *  *** NO!!!  DO NOT WANT TO LOSE INFO UNLESS SPECIFICALLY CHOSEN BY USER  ***
	 * remove table for one to many (venue to vouchers)
	 * 
	 */
}

/**** END OF DEACTIVATION CODE ****/