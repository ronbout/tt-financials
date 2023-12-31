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
				'desc' => 'Order creation date for buidling the Order Transactions table',
				'type' => 'trans_date',
				'name' => 'Transaction Start Date',
				'default' => '2020-01-01',
		),
		'transactions_cron_job' => array( 
				'id' => 'tf_financials_trans_cron_schedule',
				'desc' => 'Cron job to refresh the Transactions table',
				'type' => 'trans_cron',
				'name' => 'Transaction Refresh Cron Job',
				'default' => 'daily',
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
	// Description handling.
	$field_description = WC_Admin_Settings::get_field_description( $value );
	$description       = $field_description['description'];
	$tooltip_html      = $field_description['tooltip_html'];

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
					/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>
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


// create special settings type for the trans rebuild Cron job
add_action( 'woocommerce_admin_field_trans_cron', 'tf_financials_trans_cron_type_setup' );

function tf_financials_trans_cron_type_setup($value) {
	// Custom attribute handling.
	$custom_attributes = array();

	if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
		foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
		}
	}
	// Description handling.
	$field_description = WC_Admin_Settings::get_field_description( $value );
	$description       = $field_description['description'];
	$tooltip_html      = $field_description['tooltip_html'];

	if ( ! isset( $value['default'] ) ) {
		$value['default'] = 'daily';
	}
	if ( ! isset( $value['value'] ) ) {
		$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
	}

	$value['options'] = get_event_schedule_options();

	$cur_schedule = wp_get_schedule(TASTE_TRANS_CRON_HOOK);
	$option_value = $cur_schedule ? $cur_schedule : $value['value'];

	$cron_active = wp_next_scheduled(TASTE_TRANS_CRON_HOOK);
	if ($cron_active) {
		$t = new DateTime( );
		$t->setTimestamp( $cron_active );
		$t->setTimeZone(new DateTimeZone("America/Chicago"));
		$next_run =  $t->format("M jS g:i:s a");
		// $next_run = date("M jS g:i:s a", $cron_active);
		// $next_run = $cron_active;
		$toggle_class = 'woocommerce-input-toggle--enabled';
		$toggle_off_label = "<span style='display:none' id='tf-financial-cron-off'>Cron OFF</span>";
		$toggle_on_label = "<span id='tf-financial-cron-on'>Cron ON - Next run: 
												<span id='tf-financial-cron-next-time'> $next_run</span>
											</span>";

	} else {
		$toggle_class = 'woocommerce-input-toggle--disabled';
		$toggle_off_label = "<span id='tf-financial-cron-off'>Cron OFF</span>";
		$toggle_on_label = "<span style='display:none' id='tf-financial-cron-on'>Cron ON - Next run: 
												<span id='tf-financial-cron-next-time'> </span>
											</span>";
	}
	?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<select
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					>
					<?php
					foreach ( $value['options'] as $key => $val ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"
							<?php
							if ( is_array( $option_value ) ) {
								selected( in_array( (string) $key, $option_value, true ), true );
							} else {
								selected( $option_value, (string) $key );
							}
							?>
						><?php echo esc_html( $val ); ?></option>
						<?php
					}
					?>
				</select> 
				<span id="tf-trans-cron-toggle" class="woocommerce-input-toggle trans-cron-toggle-span <?php echo $toggle_class ?>"></span>
				<div id="trans-cron-spinner" class="spinner tf-spinner"></div>
				<span for="tf-trans-cron-toggle" id="trans-cron-toggle-label" >
						<?php echo $toggle_on_label ?>
						<?php echo $toggle_off_label ?>
				</span>
				<?php echo $description; // WPCS: XSS ok. ?>
			</td>
		</tr>

	<?php
}

function get_event_schedule_options() {
	$schedules = wp_get_schedules();
	$sched_list = array(
		'hourly',
		'two_hours',
		'twicedaily',
		'daily',
		'weekly',
	);
	$options = array();
	foreach($sched_list as $sched) {
		if (!isset($schedules[$sched])) {
			continue;
		}
		$options[$sched] = $schedules[$sched]['display'];
	}
	return $options;
}