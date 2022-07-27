<?php
/**
 *  wc-settings.php
 * 
 * 	used for creating a settings tab
 * 	in the WooCommerce settings page
 * 
 * 	7/26/2022  Ron Boutilier
 * 
 */

add_filter( 'woocommerce_settings_tabs_array', 'tf_wc_financials', 50);

function tf_wc_financials($settings_tabs) {

	$settings_tabs['tf_financials'] = 'Financials';

	return $settings_tabs;
}

add_action( 'woocommerce_settings_tabs_tf_financials', 'tf_wc_financials_settings');

function tf_wc_financials_settings() {
	woocommerce_admin_fields( get_tf_wc_financials_settings());
}

add_action( 'woocommerce_update_options_tf_financials', 'tf_wc_financials_update_settings');

function tf_wc_financials_update_settings() {
	woocommerce_update_options( get_tf_wc_financials_settings());
}

function get_tf_wc_financials_settings() {
	$settings = array(
		'section_title' => array( 
				'id' => 'tf_financials_section_title',
				'desc' => 'Venues / Vouchers / Transactions Information',
				'type' => 'title',
				'name' => 'Venue Financials Settings',
		),
		'transactions_default_start_date' => array( 
				'id' => 'tf_financials_trans_start_date',
				'desc' => 'Default order creation date for buidling the Order Transactions table',
				'type' => 'text',
				'name' => 'Transaction Start Date',
				'default' => '2020-01-01',
		),
		'rounding_threshold_pbo' => array( 
				'id' => 'tf_financials_rounding_threshold',
				'desc' => 'Maximum adjustment that can be made to PBO Payment amount to zero out the Balance Due',
				'type' => 'number',
				'name' => 'PBO Rounding Threshold',
				'default' => '0.1',
		),
		'section_end' => array( 
				'id' => 'tf_financials_section_end',
				'type' => 'sectionend',
		),
	);
	return $settings;
}