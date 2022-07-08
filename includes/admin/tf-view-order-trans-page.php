<?php
/**
 *  tf-view-order-trans-page.php 
 *  Sets up the order transactions admin page
 *  using Taste_List_Table Class from WP_list_class
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

function tf_view_order_trans() {
  tf_build_trans_admin_list_table();
}
/******************************
 * TFTRans_list_table Class
 ******************************/
class TFTRans_list_table extends Taste_list_table {

  public function __construct() {
    parent::__construct(
      array(
        'singular' => "Transaction",
        'plural' => "Transactions",
        'ajax' => true,
      )
    );
  }

  public function get_columns() {
    $ret_array =  array(
			'cb' => '<input type="checkbox" >',
			'id' => 'ID',
      'order_id' => "Order ID",
      'order_item_id' => "Order Item ID",
			'transaction_date' => "Transaction Date",
      'trans_type' => "Transaction Type",
      'trans_amount' => "Amount",
			'trans_entry_timestamp' => "Transaction Record<br>Creation Date",
			'batch_id' => "Batch ID",
			'batch_timestamp' => "Batch Date",
			'order_date' => "Order Date",
			'product_id' => "Product ID",
			'product_price' => "Product Price",
			'quantity' => "Item Quantity",
			'gross_revenue' => "Gross Revenue",
			'venue_id' => "Venue ID",
			'venue_name' => "Venue Name",
			'taste_credit_coupon_id' => "Store Credit<br>Coupon ID",
      'refund_id' => "Refund ID",
      'coupon_id' => "Applied<br>Coupon ID",
      'coupon_value' => "Applied<br>Coupon Value",
      'net_cost' => "Net Cost",
      'commission' => "Commission",
      'vat' => "VAT",
      'gross_income' => "Gross Income",
      'venue_due' => "Venue Due",
      'payment_id' => "Payment ID",
      'payment_status' => "Payment Status",
      'payment_date' => "Payment Date",
      'redemption_date' => "Redemption Date",
    );

    return $ret_array;
   }
   
   public function column_venue_id($item) {
    $venue_id = $item['venue_id'];
    $cm_link = get_site_url(null, "/campaign-manager/?venue-id={$venue_id}");
      return "
        <a href='$cm_link' target='_blank'>$venue_id</a>
        ";
   }

  public function column_default($item, $column_name) {
    switch($column_name) {
      case 'order_id':
      case 'product_id':
        $col_id = $item[$column_name];
        $col_link = get_edit_post_link($col_id);
          return "
            <a href='$col_link' target='_blank'>$col_id</a>
            ";
        break;
      case 'id':
      case 'order_item_id':
      case 'trans_type':
      case 'transaction_date':
      case 'trans_amount':
      case 'order_date':
      case 'product_id':
      case 'venue_name':
      case 'net_cost':
      case 'gross_income':
      case 'venue_due':
      case 'payment_id':
        return $item[$column_name] ? $item[$column_name] : "N/A";
      default:
      return $item[$column_name] ? $item[$column_name] : "N/A";
    }
  }

  public function get_hidden_columns() {
    $hidden_cols = array(
      'trans_entry_timestamp',
      'batch_id',
      'batch_timestamp',
      'product_price',
      'quantity',
      'gross_revenue',
      'taste_credit_coupon_id',
      'refund_id',
      'coupon_id',
      'coupon_value',
      'commission',
      'vat',
      'payment_status',
      'payment_date',
      'redemption_date',
    );
    
    return $hidden_cols;
  }

  public function get_sortable_columns() {
    $sort_array = array(
      'id' => array('id', true),
      'order_id' => array('order_id', true),
      'venue_id' => array('venue_id', true),
      'product_id' => array('product_id', true),
    );
    return $sort_array;
  }

	public function column_cb($item) {
		return "<input type='checkbox' name='ot-list-cb' value='{$item['id']}'";
	}
  
  public function no_items() {
    echo "No transactions found.";
  }

  public function load_trans_table($order_by="id", $order="DESC", $per_page=20, $page_number=1) {
    global $wpdb;

    $offset = ($page_number - 1) * $per_page;
  
    $sql = "
      SELECT *
      FROM {$wpdb->prefix}taste_order_transactions oit_o 
      ORDER BY oit_o.$order_by $order, oit_o.transaction_date ASC
      LIMIT $per_page
      OFFSET $offset;
      ";
  
    $trans_rows = $wpdb->get_results($sql, ARRAY_A);
    
    return $trans_rows;
  }
  
  public function prepare_items() {
    $get_vars = $this->check_list_get_vars();
    $order_by = $get_vars['order_by'] ? $get_vars['order_by'] : 'id';
    $order = $get_vars['order'] ? $get_vars['order'] : 'DESC';

    $this->items = $this->load_trans_table($order_by, $order);

    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);
  }

  public function check_list_get_vars() {
    $order_by = isset($_GET['orderby']) ? $_GET['orderby'] : '';
    $order = isset($_GET['order']) ? $_GET['order'] : '';

    return compact('order_by', 'order');
  }
}
/***********************************
 * End of TFTRans_list_table Class
 ***********************************/

function tf_build_trans_admin_list_table() {
  $tf_trans_table = new TFTRans_list_table();

  $tf_trans_table->get_columns();
  $tf_trans_table->prepare_items();
  ?>
	<div class="wrap">    
		<h2>Order Transactions</h2>
		<div id="tf_order_trans">			
			<div id="tf_post_body">		
				<form id="tf-order-trans-form" method="get">					
					<?php $tf_trans_table->display(); ?>					
				</form>
			</div>			
		</div>
	</div>
	<?php
}

