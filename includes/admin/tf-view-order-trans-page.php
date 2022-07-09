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
   
   protected function column_venue_id($item) {
    $venue_id = $item['venue_id'];
    $cm_link = get_site_url(null, "/campaign-manager/?venue-id={$venue_id}");
      return "
        <a href='$cm_link' target='_blank'>$venue_id</a>
        ";
   }

  protected function column_default($item, $column_name) {
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

  protected function get_hidden_columns() {
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

  protected function get_views() {
    $get_string_array = tf_check_query(true);

    $get_string = '';
    foreach($get_string_array as $get_var => $get_val) {
      if ('trans-type' == $get_var) {
        continue;
      }
      $get_string .= $get_string ? '&' : '';
      $get_string .= $get_var . "=" . $get_val;
    }

    $list_link = "admin.php?$get_string";

    $trans_types_counts = $this->count_trans_types();

    $tot_cnt = 0;
    foreach ($trans_types_counts as $trans_type_info) {
      $t_cnt = $trans_type_info['trans_count'];
      $tot_cnt += (int) $t_cnt;
      $trans_type = $trans_type_info['trans_type'];
      $trans_type = str_replace(' - ', '_', $trans_type);
      $trans_type = str_replace(' ', '_', $trans_type);
      $trans_type = strtolower($trans_type);
      $t_cnt = number_format($t_cnt);

      $tmp_views[$trans_type] = "<a href='${list_link}&trans-type=$trans_type'>{$trans_type_info['trans_type']} ($t_cnt)</a>";
    }
    $tot_cnt = number_format($tot_cnt);
    $trans_type_views = array(
      'all' => "<a href='${list_link}'>All ($tot_cnt)</a>"
    );

    $trans_type_views = array_merge($trans_type_views, $tmp_views);

    return $trans_type_views;
  }

  protected function extra_tablenav($which) {
    if ('top' == $which) {
      $venue_list = $this->get_venue_list();
      $options_list = "          
        <option value='0'>
       		Select By Venue
        </option>";
      foreach($venue_list as $venue_info) {
        $venue_id = $venue_info['venue_id'];
        $venue_name = $venue_info['name'];
        $options_list  .= "<option value='$venue_id'>$venue_name</option> ";
      }
      ?>
      <div class="alignleft actions">
        <select name="venue-selection" id="trans-list-venue-selection">
					<?php echo $options_list ?>
        </select>
        <input type="submit" name="filter_action" id="trans-list_submit" class="button" value="Filter">
      </div>

      <?php
    }
  }

  protected function get_sortable_columns() {
    $sort_array = array(
      'id' => array('id', true),
      'order_id' => array('order_id', true),
      'venue_id' => array('venue_id', true),
      'product_id' => array('product_id', true),
    );
    return $sort_array;
  }

  protected function get_bulk_actions() {
    $bulk_actions = array(
      'bulk-export' => "Export",
    );
    return $bulk_actions;
  }

	protected function column_cb($item) {
		return "<input type='checkbox' name='ot-list-cb' value='{$item['id']}'";
	}
  
  public function no_items() {
    echo "No transactions found.";
  }
  
  public function prepare_items() {
    $get_vars = $this->check_list_get_vars();
    $order_by = $get_vars['order_by'] ? $get_vars['order_by'] : 'id';
    $order = $get_vars['order'] ? $get_vars['order'] : 'DESC';
    $trans_type = $get_vars['trans_type'] ? $get_vars['trans_type'] : '';

    $per_page = $this->get_user_per_page_option();
    $page_num = $this->get_pagenum();
    $trans_count = $this->count_trans_table();
    $pagination_args = array( 
      'total_items' => $trans_count,
      'per_page' => $per_page,
    );
    $this->set_pagination_args($pagination_args);

    $this->items = $this->load_trans_table($order_by, $order, $per_page, $page_num, $trans_type);

    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);
  }

  protected function check_list_get_vars() {
    $order_by = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : '';
    $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : '';
    $trans_type = isset($_REQUEST['trans-type']) ? $_REQUEST['trans-type'] : '';

    return compact('order_by', 'order', 'trans_type');
  }

  protected function get_user_per_page_option() {
    $user = get_current_user_id();
    $screen = get_current_screen();
    $option = $screen->get_option( 'per_page', 'option' );
    $per_page = get_user_meta( $user, $option, true );
    if (empty( $per_page ) || !$per_page) {
      $per_page = $screen->get_option( 'per_page', 'default' );
    }
    return $per_page;
  }

  protected function load_trans_table($order_by="id", $order="DESC", $per_page=20, $page_number=1, $trans_type='') {
    global $wpdb;

    $offset = ($page_number - 1) * $per_page;
    $trans_test = '';

    if ($trans_type) {
      $trans_types = array( 
        'order' => "Order",
        'redemption' => "Redemption",
        'creditor_payment' => "Creditor Payment",
        'refund' => "Refund",
        'taste_credit' => "Taste Credit",
        'order_from_credit' => "Order - From Credit",
        'redemption_from_credit' => "Redemption - From Credit",
      );
      $trans_test = "WHERE oit.trans_type = %s";
      $db_trans_type = $trans_types[$trans_type];
    }
  
    $sql = "
      SELECT *
      FROM {$wpdb->prefix}taste_order_transactions oit
      $trans_test
      ORDER BY oit.$order_by $order, oit.transaction_date ASC
      LIMIT $per_page
      OFFSET $offset;
      ";

    if ($trans_test) {
      $sql = $wpdb->prepare($sql, $db_trans_type);
    }
  
    // echo '<h1>', $sql, '</h1>';
    // die();
    $trans_rows = $wpdb->get_results($sql, ARRAY_A);
    
    return $trans_rows;
  }

  protected function count_trans_table() {
    global $wpdb;

    $sql = "
      SELECT COUNT(*)
      FROM {$wpdb->prefix}taste_order_transactions
      ";
  
    $trans_count = $wpdb->get_var($sql);
    
    return $trans_count;
  }

  protected function count_trans_types() {
    global $wpdb;

    $sql = "
      SELECT oit.trans_type, COUNT(*) AS trans_count
      FROM {$wpdb->prefix}taste_order_transactions oit
      GROUP BY oit.trans_type
      ";
  
    $trans_types_count = $wpdb->get_results($sql, ARRAY_A);
    
    return $trans_types_count;
  }
  
  protected function get_venue_list() {
    global $wpdb;

    $venue_rows = $wpdb->get_results("
		SELECT venue_id, name, description, venue_type
		FROM " . $wpdb->prefix . "taste_venue
		ORDER BY name
	", ARRAY_A);
    
    return $venue_rows;
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
        <?php $tf_trans_table->views() ?>
				<form id="tf-order-trans-form" method="get">					
					<?php $tf_trans_table->display(); ?>					
				</form>
			</div>			
		</div>
	</div>
	<?php
}

