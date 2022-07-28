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
				'type' => 'trans_date',
				'name' => 'Transaction Start Date',
				'default' => '2020-01-01',
		),
		'rounding_threshold_pbo' => array( 
				'id' => 'tf_financials_rounding_threshold',
				'desc' => 'Maximum adjustment that can be made to PBO Payment amount to zero out the Balance Due',
				'type' => 'number',
				'name' => 'PBO Rounding Threshold',
				'default' => '0.1',
				'custom_attributes' => array(
					'step' => '0.01',
					'min' => '0'
				),
		),
		'section_end' => array( 
				'id' => 'tf_financials_section_end',
				'type' => 'sectionend',
		),
	);
	return $settings;
}

// create special settings type to include the Rebuild and Refresh Order Transactions buttons
add_action( 'woocommerce_admin_field_trans_date', 'tf_financials_trans_date_type_setup' );

function tf_financials_trans_date_type_setup($value) {
	// Custom attribute handling.
	$custom_attributes = array();
  add_thickbox();

	if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
		foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
		}
	}

	if ( ! isset( $value['default'] ) ) {
		$value['default'] = '2020-01-01';
	}
	if ( ! isset( $value['value'] ) ) {
		$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
	}

	$option_value = $value['value'];
	?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="date"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					/>
			</td>
		</tr>
		<tr>
			<th	scope="row" class="titledesc">Update the Transactions Table</th>
			<td>
				<input type="hidden" id="trans_update_start_date" value="<?php echo $option_value ?>">
				<button data-page="wc-settings" id="run-build-trans" type="button" class="button">Refresh</button>
				<div id="trans-update-spinner" class="spinner tf-spinner"></div>
		</tr>
		<tr>
			<th	scope="row" class="titledesc">Delete and Rebuild the Transactions Table</th>
			<td>
				<button data-page="wc-settings" id="run-rebuild-trans" type="button" class="button">Rebuild</button>
				<div id="trans-update-spinner-rebuild" class="spinner tf-spinner"></div>
			</td>
		</tr>
    <div id="trans-refresh-results" style="display:none;"></div>
	<?php
}
