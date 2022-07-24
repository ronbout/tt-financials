<?php
/**
 * 	calc_payments_details.php
 * 	code for the add_payment_details method
 *	of the payments admin list page
 *	
 *	This code will just be inserted into that method
 *	It has $payment_rows as the parameter that will
 *	be available in this file
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

global $wpdb;

// get list of payments
$payment_ids = array_column($payment_rows, 'payment_id');

//  retrieve prod info price, comm rate

$placeholders = array_fill(0, count($payment_ids), '%d');
$placeholders = implode(', ', $placeholders);

$sql = "
	SELECT pprods.payment_id, pprods.amount, pprods.product_id, pm_price.meta_value AS price,
		pm_comm.meta_value AS commission, oix.order_item_id AS pbo_flag
	FROM {$wpdb->prefix}taste_venue_payment_products pprods
		LEFT JOIN {$wpdb->prefix}postmeta pm_price ON pm_price.post_id = pprods.product_id AND pm_price.meta_key = '_sale_price'
		LEFT JOIN {$wpdb->prefix}postmeta pm_comm ON pm_comm.post_id = pprods.product_id AND pm_comm.meta_key = 'commission'
		LEFT JOIN {$wpdb->prefix}taste_venue_payment_order_item_xref oix ON oix.payment_id = pprods.payment_id
	WHERE pprods.payment_id IN ($placeholders)
	GROUP BY pprods.payment_id, pprods.product_id
	ORDER BY pprods.payment_id
";

$sql = $wpdb->prepare($sql, $payment_ids);
$pay_prod_info_rows = $wpdb->get_results($sql, ARRAY_A);

$payment_rows_w_details = array_map(function ($payment_row) use ($pay_prod_info_rows) {
	$col_count = $this->get_column_count();
	$tmp_row = $payment_row;
	$payment_id = $tmp_row['payment_id'];

	// filter payment prod rows by payment id to get all the details for this payment
	$pay_prod_rows = array_filter($pay_prod_info_rows, function ($row) use ($payment_id) {
		return $payment_id == $row['payment_id'];
	});

	$details = "<td colspan='$col_count'>";
	$details .= build_details_table($pay_prod_rows, $tmp_row, $this);
	$details .= "</td>";
	$tmp_row['details'] = $details;
	$tmp_row['actions'] = $payment_row['payment_id'];
	return $tmp_row;
}, $payment_rows);


function build_details_table($pay_prod_rows, $payment_row, $this_ref) {
	$payment_date = $payment_row['payment_date'];
	$payment_status = $payment_row['payment_status'];
	ob_start();
	?>
		<div class="payment-details-container">
			<table class="payment-details-table widefat fixed">
				<thead>
					<tr>
						<th class="tf-aligncenter">Product ID</th>
						<th class="tf-aligncenter">Staged Payment</th>
						<th class="tf-alignright">Price</th>
						<th class="tf-alignright">Quantity Sold</th>
						<th class="tf-alignright">Gross Sales</th>
						<th class="tf-alignright">Commission %</th>
						<th class="tf-alignright">Commission Amount</th>
						<th class="tf-alignright">VAT %</th>
						<th class="tf-alignright">VAT Amount</th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach($pay_prod_rows as $pay_prod_row) {
						$payment_id = $pay_prod_row['payment_id'];
						$product_id = $pay_prod_row['product_id'];
						$payment_amount = $pay_prod_row['amount'];
						$pbo_flag = $pay_prod_row['pbo_flag'] ? "Yes" : "no";
						$price = $pay_prod_row['price'];
						$comm_rate = $pay_prod_row['commission'];
						$inv_calcs = tf_comm_vat_per_payment($payment_amount, $comm_rate, $payment_date);
						$vat_rate = $inv_calcs['vat_val'];
						$pay_gross = $inv_calcs['pay_gross'];
						$pay_vat = $inv_calcs['pay_vat'];
						$pay_comm = $inv_calcs['pay_comm'];
						$qty = round( $pay_gross / $price);

						$price = number_format($price,2);
						$payment_amount = number_format($payment_amount,2);
						$pay_gross = number_format($pay_gross,2);
						$pay_vat = number_format($pay_vat,2);
						$pay_comm = number_format($pay_comm,2);

						$trans_linkable = (TASTE_PAYMENT_STATUS_ADJ != $payment_status && $pbo_flag);

						if (!$trans_linkable) {
							$product_id_disp = $product_id;
						} else {
							$pay_prod_link = esc_url(get_admin_url( null, "admin.php?page=view-order-transactions&payment-id=$payment_id&product-id=$product_id"));
							$title = "View Product $product_id of Payment {$payment_row['payment_id']} in the Order Transactions Page";
							$product_id_disp = "<a title='$title' href='$pay_prod_link'>$product_id</a>";
						}
						$product_id_disp = $this_ref->add_filter_by_action($product_id, 'product_id', $product_id_disp);
						
						echo "
						<tr>
							<td>$product_id_disp</td>
							<td class='tf-alignright'>$payment_amount</td>
							<td class='tf-alignright'>$price</td>
							<td class='tf-alignright'>$qty</td>
							<td class='tf-alignright'>$pay_gross</td>
							<td class='tf-alignright'>$comm_rate</td>
							<td class='tf-alignright'>$pay_comm</td>
							<td class='tf-alignright'>$vat_rate</td>
							<td class='tf-alignright'>$pay_vat</td>
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
