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
		pm_comm.meta_value AS commission
	FROM {$wpdb->prefix}taste_venue_payment_products pprods
		LEFT JOIN {$wpdb->prefix}postmeta pm_price ON pm_price.post_id = pprods.product_id AND pm_price.meta_key = '_sale_price'
		LEFT JOIN {$wpdb->prefix}postmeta pm_comm ON pm_comm.post_id = pprods.product_id AND pm_comm.meta_key = 'commission'
	WHERE pprods.payment_id IN ($placeholders)
	ORDER BY pprods.payment_id
";

$sql = $wpdb->prepare($sql, $payment_ids);
$pay_prod_info_rows = $wpdb->get_results($sql, ARRAY_A);

// echo "<pre>";
// print_r($pay_prod_info_rows);
// echo "</pre>";


// build table row of that info


$payment_rows_w_details = array_map(function ($payment_row) use ($col_count, $pay_prod_info_rows) {
	$tmp_row = $payment_row;
	$payment_id = $tmp_row['payment_id'];

	// filter payment prod rows by payment id to get all the details for this payment
	$pay_prod_rows = array_filter($pay_prod_info_rows, function ($row) use ($payment_id) {
		return $payment_id == $row['payment_id'];
	});

	$details = "<td colspan='$col_count'>";
	$details .= build_details_table($pay_prod_rows, $tmp_row['payment_date']);
	$details .= "</td>";
	$tmp_row['details'] = $details;
	$tmp_row['actions'] = $payment_row['payment_id'];
	return $tmp_row;
}, $payment_rows);


function build_details_table($pay_prod_rows, $payment_date) {
	ob_start();
	?>
		<table>
			<thead>
				<tr>
					<th>Product ID</th>
					<th>Staged Payment</th>
					<th>Price</th>
					<th>Quantity Sold</th>
					<th>Gross Sales</th>
					<th>Commission %</th>
					<th>Commission Amount</th>
					<th>VAT %</th>
					<th>VAT Amount</th>
				</tr>
			</thead>
			<tbody>
		<?php
			foreach($pay_prod_rows as $pay_prod_row) {
				$payment_amount = $pay_prod_row['amount'];
				$price = $pay_prod_row['price'];
				$comm_rate = $pay_prod_row['commission'];
				$inv_calcs = tf_comm_vat_per_payment($payment_amount, $comm_rate, $payment_date);
				$vat_rate = $inv_calcs['vat_val'];
				$pay_gross = $inv_calcs['pay_gross'];
				$pay_vat = $inv_calcs['pay_vat'];
				$pay_comm = $inv_calcs['pay_comm'];
				$qty = $pay_gross / $price;
				
				echo "
				<tr>
					<td>{$pay_prod_row['product_id']}</td>
					<td>$payment_amount</td>
					<td>$price</td>
					<td>$qty</td>
					<td>$pay_gross</td>
					<td>$comm_rate</td>
					<td>$pay_comm</td>
					<td>$vat_rate</td>
					<td>$pay_vat</td>
				</tr>";
			}
			?>
			</tbody>
		</table>
	<?php
	$ret_table = ob_get_clean();
	return $ret_table;
}
