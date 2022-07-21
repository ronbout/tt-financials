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
	$col_count = count($this->get_columns());
	// $col_count2 = $this->get_column_count();
	$hidden_col_cnt = count($this->get_hidden_columns());
	$display_col_cnt = $col_count - $hidden_col_cnt;

	// echo "<h2>col count: ", $col_count, "</h2>";
	// echo "<h2>col count2: ", $col_count2, "</h2>";
	// echo "<h2>hidden col count: ", $hidden_col_cnt, "</h2>";
	// echo "<h2>disp col count: ", $display_col_cnt, "</h2>";

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
						<th class="tf-aligncenter">Quantity<br> Redeemed</th>
						<th class="tf-aligncenter">Comm %</th>
						<th class="tf-aligncenter">Price</th>
						<th class="tf-aligncenter">Gross Revenue</th>
						<th class="tf-aligncenter">Comm <br> Amount</th>
						<th class="tf-aligncenter">VAT <br>Amount</th>
						<th class="tf-aligncenter">Net <br>Payable</th>
						<th class="tf-aligncenter">Paid Amt</th>
						<th class="tf-aligncenter">Balance<br> Due</th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach($product_rows as $prod_row) {
						$product_id = $prod_row['product_id'];
						$price = $prod_row['price'];
						$prod_title = $prod_row['title'];
						$status = $prod_row['status'];
						$qty_sold = $prod_row['order_qty'];
						$qty_redeem = $prod_row['redeemed_qty'];
						$comm_rate = $prod_row['commission_rate'];
						$price = $prod_row['price'];
						$gr_revenue = $prod_row['revenue'];
						$comm_amount = $prod_row['commission'];
						$vat_amount = $prod_row['vat'];
						$paid_amount = $prod_row['paid_amount'];
						$net_payable = $prod_row['net_payable'];
						$balance_due = $prod_row['balance_due'];

						$price = number_format($price,2);
						$gr_revenue = number_format($gr_revenue,2);
						$comm_amount = number_format($comm_amount,2);
						$vat_amount = number_format($vat_amount,2);
						$paid_amount = number_format($paid_amount,2);
						$net_payable = number_format($net_payable,2);
						$balance_due = number_format($balance_due,2);
						
						echo "
						<tr title='$prod_title'>
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