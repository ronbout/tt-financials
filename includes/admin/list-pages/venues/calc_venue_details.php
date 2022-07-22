<?php
/**
 * 	calc_venue_details.php
 * 	code for the add_venue_details method
 *	of the venues admin list page
 *	
 *	This code will just be inserted into that method
 *	It has $venue_rows and balance filter as the parameters
 *	that will	be available in this file
 *	
 *	This routine is responsible for setting up the make payment code
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');
global $wpdb;

$venue_id_list = array_column($venue_rows, 'venue_id');

foreach($venue_rows as &$venue_row) {
	$venue_id = $venue_row['venue_id'];
	$col_count = count($this->get_columns());
	// $col_count2 = $this->get_column_count();
	$hidden_col_cnt = count($this->get_hidden_columns());
	$display_col_cnt = $col_count - $hidden_col_cnt;

	$product_calcs = $venue_row['prod_calcs'];
		
	$details = "<td colspan='$display_col_cnt'>";
	$details .= build_venue_details_table($product_calcs, $venue_id, $balance_filter, $this);
	$details .= "</td>";
	$venue_row['details'] = $details;
	unset($venue_row['prod_calcs']);

}

/************************
 * start of functions
 ************************/

function build_venue_details_table($product_rows, $venue_id, $balance_filter='', $this_obj) {
	$product_rows = $this_obj->filter_by_balance_due($product_rows, $balance_filter);
	$payment_date = $payment_row['payment_date'];
	$payment_status = $payment_row['payment_status'];
	ob_start();
	?>
		<div class="venue-details-container">
			<table class="venue-details-table widefat fixed">
				<thead>
					<tr>
						<th>Pay</th>
						<th class="tf-aligncenter">Product ID</th>
						<th class="tf-aligncenter">Status</th>
						<th class="tf-aligncenter">Quantity Sold</th>
						<th class="tf-aligncenter">Quantity<br> Redeem</th>
						<th class="tf-aligncenter">Comm %</th>
						<th class="tf-aligncenter">Price</th>
						<th class="tf-aligncenter">Gross Revenue</th>
						<th class="tf-aligncenter">Comm <br> Amount</th>
						<th class="tf-aligncenter">VAT <br>Amount</th>
						<th class="tf-aligncenter">Net <br>Payable</th>
						<th class="tf-aligncenter">Paid Amt</th>
						<th class="tf-aligncenter">Balance<br> Due</th>
						<th class="tf-aligncenter">Selected<br>Order Qty</th>
						<th class="tf-aligncenter">Selected<br>Pay Amt</th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach($product_rows as $prod_row) {
						$product_id = $prod_row['product_id'];
						$balance_due = $prod_row['balance_due'];
						$price = $prod_row['price'];
						$prod_title = $prod_row['title'];
						$status = $prod_row['status'];
						$qty_sold = $prod_row['order_qty'];
						$qty_redeem = $prod_row['redeemed_qty'];
						$vat_rate = $prod_row['vat_rate'];
						$comm_rate = $prod_row['commission_rate'];
						$price = $prod_row['price'];
						$gr_revenue = $prod_row['revenue'];
						$comm_amount = $prod_row['commission'];
						$vat_amount = $prod_row['vat'];
						$paid_amount = $prod_row['paid_amount'];
						$net_payable = $prod_row['net_payable'];

						if ($balance_due) {
							$unpaid_order_info = get_unpaid_order_info($product_id, $balance_due, $price, $comm_rate, $vat_rate);

							$potential_order_qty = $unpaid_order_info['orderQty'];
							$potential_order_amount = $unpaid_order_info['netPayable'];
							$selected_order_qty = 0;
							$selected_order_amount = 0;
							$payment_data = serialize($unpaid_order_info['orderItemList']);
						} else {
							$potential_order_qty = 0;
							$potential_order_amount = 0;
							$selected_order_qty = 0;
							$selected_order_amount = 0;
							$payment_data = '';
						}

						$price = number_format($price,2);
						$gr_revenue = number_format($gr_revenue,2);
						$comm_amount = number_format($comm_amount,2);
						$vat_amount = number_format($vat_amount,2);
						$paid_amount = number_format($paid_amount,2);
						$net_payable = number_format($net_payable,2);
						$balance_due = number_format($balance_due,2);
						$selected_order_amount = number_format($selected_order_amount,2);
						
						echo "
						<tr  id='details-row-$venue_id-$product_id' class='details-row-$venue_id' title='$prod_title' 
																		data-order-info='$payment_data'>
							<th scope='row' >
								<input type='checkbox' class='check-venue-product-payment venue-payment-$venue_id'
												name='venue-product-payment-cb' value='$venue_id-$product_id' >
							</th>
							<td>$product_id</td>
							<td>$status</td>
							<td class='tf-alignright'>$qty_sold</td>
							<td class='tf-alignright'>$qty_redeem</td>
							<td class='tf-alignright'>$comm_rate</td>
							<td class='tf-alignright'>$price</td>
							<td class='tf-alignright'>$gr_revenue</td>
							<td class='tf-alignright'>$comm_amount</td>
							<td class='tf-alignright'>$vat_amount</td>
							<td class='tf-alignright'>$net_payable</td>
							<td class='tf-alignright'>$paid_amount</td>
							<td class='tf-alignright'>$balance_due</td>
							<td class='tf-alignright'>
								<span id='oq-$venue_id-$product_id' data-qty='$potential_order_qty' class='select-order-qty'>
									$selected_order_qty
								<span>
							</td>
							<td class='tf-alignright'>
								<span id='oa-$venue_id-$product_id' data-amt='$potential_order_amount' class='select-order-amt'>
									$selected_order_amount
								<span>
							</td>
						</tr>";
					}
					?>
				</tbody>
			</table>
		</div>
	<?php
	$ret_table = ob_get_clean();
	return $ret_table;
}

function get_unpaid_order_info($product_id, $balance_due, $price, $comm_rate, $vat_rate) {
	// calc the net payable per order
	$net_payable_per_order = tf_calc_net_payable($price, $vat_rate , $comm_rate, 1, false)['net_payable'];

	// calc how many orders it would take to fill that amount
	$needed_order_qty = (int) floor(($balance_due / $net_payable_per_order) + 0.005);
	if (!$needed_order_qty) {
		return  array(
			'netPayable' => '0.00',
			'orderQty' => 0,
			'neededOrderQty' => 0,
			'orderItemList' => array()
		);
	}
	$selected_order_info = build_payment_with_orders($product_id, $needed_order_qty, $price, $vat_rate, $comm_rate);
	return $selected_order_info;
}

function build_payment_with_orders($prod_id, $needed_order_qty, $price, $vat_rate, $comm_rate) {
	global $wpdb;

	$sql = "
		SELECT im.meta_value AS quan, o.post_date,
			wclook.product_id AS productID,
			i.order_id, i.order_item_id as itemid
		FROM {$wpdb->prefix}wc_order_product_lookup wclook
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON im.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items i ON i.order_item_id = wclook.order_item_id
			LEFT JOIN {$wpdb->prefix}posts o ON o.id = wclook.order_id
			LEFT JOIN {$wpdb->prefix}taste_venue_payment_order_item_xref poix ON poix.order_item_id = wclook.order_item_id
		WHERE im.meta_key = '_qty'
			AND i.downloaded = 1
			AND o.post_status = 'wc-completed'
			AND o.post_type = 'shop_order'
			AND poix.payment_id IS NULL 
			AND wclook.product_id = %d
		GROUP BY o.id
		ORDER BY o.post_date ASC
		LIMIT %d
	";

	$targeted_orders = $wpdb->get_results($wpdb->prepare($sql, $prod_id, $needed_order_qty), ARRAY_A);
	
	$total_qty = 0;
	$product_list = array();
	$trouble_product_list = array();

	$targeted_orders = $wpdb->get_results($wpdb->prepare($sql . $limit_clause, $prod_id, $needed_order_qty), ARRAY_A);
	if (!count($targeted_orders)) {
		return  array(
			'netPayable' => 0.00,
			'orderQty' => 0,
			'neededOrderQty' => 0,
			'orderItemList' => array()
		);
	}
	$prod_qty = 0;
	$tmp_order_array = array();
	foreach ($targeted_orders as $order_info) {
		$ord_qty = $order_info['quan'];
		if ($prod_qty + $ord_qty > $needed_order_qty ){
			break;
		} 
		$prod_qty += $ord_qty;
		$tmp_order_array[] = array(
			'orderItemId' => $order_info['itemid'],
			'orderId' => $order_info['order_id'],
			'orderQty' => $ord_qty,
		);
	}

	$prod_net_payable = tf_calc_net_payable($price, $vat_rate , $comm_rate, $prod_qty, true)['net_payable'];

	$ret_array = array(
		'netPayable' => $prod_net_payable,
		'orderQty' => $prod_qty,
		'neededOrderQty' => $needed_order_qty,
		'orderItemList' => $tmp_order_array
	);

	return $ret_array;		
}