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
      'order_id' => "Order ID",
      'order_item_id' => "Order Item <br>ID",
			'transaction_date' => "Transaction Date",
      'trans_type' => "Transaction Type",
      'trans_amount' => "Amount",
			'trans_entry_timestamp' => "Transaction Record<br>Creation Date",
			'batch_id' => "Batch ID",
			'batch_timestamp' => "Batch Date",
			'order_date' => "Order Date",
			'product_id' => "Product<br>  ID",
			'product_price' => "Product Price",
			'quantity' => "Item Quantity",
			'gross_revenue' => "Gross Revenue",
      'customer_id' => "Customer<br>  ID",
      'customer_name' => "Customer<br>Name",
      'customer_email' => "Customer<br>Email",
			'venue_id' => "Venue ID",
			'venue_name' => "Venue Name",
			'taste_credit_coupon_id' => "Store Credit<br>Coupon ID",
      'refund_id' => "Refund ID",
      'coupon_id' => "Applied<br>Coupon  ID",
      'coupon_value' => "Applied<br>Coupon Value",
      'net_cost' => "Net Cost",
      'commission' => "Commission",
      'vat' => "VAT",
      'gross_income' => "Gross Income",
      'venue_due' => "Venue Due",
      'payment_id' => "Payment<br>  ID",
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
      case 'venue_name':
      case 'net_cost':
      case 'gross_income':
      case 'customer_id':
      case 'customer_name':
      case 'customer_email':
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
      'customer_id',
      'customer_email',
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
		$get_string = tf_check_query(false);
    $cur_trans_type = isset($_REQUEST['trans-type']) && $_REQUEST['trans-type'] ? $_REQUEST['trans-type'] : 'all';
		$get_string = remove_query_arg( 'trans-type', $get_string ); 

    $list_link = "admin.php?$get_string";

    $trans_types_counts = $this->count_trans_types();

    $tot_cnt = 0;
    foreach ($trans_types_counts as $t_type => $t_cnt) {
      $tot_cnt += (int) $t_cnt;
      $trans_type = $this->convert_trans_type_to_slug( $t_type);
      $t_cnt = number_format($t_cnt);

      if ($cur_trans_type == $trans_type ) {
        $tmp_views[$trans_type] = "<strong>{$t_type} ($t_cnt)</strong>";
      } else {
        $tmp_views[$trans_type] = "<a href='${list_link}&trans-type=$trans_type'>{$t_type} ($t_cnt)</a>";
      }
    }
    $tot_cnt = number_format($tot_cnt);
    if ("all" == $cur_trans_type) {
      $trans_type_views = array(
        'all' => "<strong>All ($tot_cnt)</strong>"
      );
    } else {
      $trans_type_views = array(
        'all' => "<a href='${list_link}'>All ($tot_cnt)</a>"
      );
    }

    $trans_type_views = array_merge($trans_type_views, $tmp_views);

    return $trans_type_views;
  }

  protected function extra_tablenav($which) {
    if ('top' == $which) {
			$get_vars = $this->check_list_get_vars();
			$filters = $get_vars['filters'];
			$venue_select = isset($filters['venue_id']) ? $filters['venue_id'] : -1;

      $venue_list = $this->get_venue_list();
      $options_list = "          
        <option value='-1' " . (-1 == $venue_select ? " selected " : "") . ">
       		Select By Venue
        </option>          
        <option value='0' " . (0 == $venue_select ? " selected " : "") . ">
       		Unassigned
        </option>";

      foreach($venue_list as $venue_info) {
        $venue_id = $venue_info['venue_id'];
        $venue_name = $venue_info['name'];
        $options_list  .= "<option value='$venue_id' " . ($venue_id == $venue_select ? " selected " : "") . ">$venue_name</option> ";
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
      'order_id' => array('order_id', true),
      'venue_id' => array('venue_id', true),
      'transaction_date' => array('transaction_date', true),
      'trans_type' => array('trans_type', true),
      'trans_amount' => array('trans_amount', true),
      'order_date' => array('order_date', true),
      'product_id' => array('product_id', true),
      'customer_id' => array('customer_id', true),
      'customer_name' => array('customer_name', true),
      'customer_email' => array('customer_email', true),
      'venue_id' => array('venue_id', true),
      'venue_name' => array('venue_name', true),
      'net_cost' => array('net_cost', true),
      'gross_income' => array('gross_income', true),
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
    $order_by = $get_vars['order_by'] ? $get_vars['order_by'] : 'transaction_date';
    $order = $get_vars['order'] ? $get_vars['order'] : 'DESC';
		$filters = $get_vars['filters'];
			
    $per_page = $this->get_user_per_page_option();
    $page_num = $this->get_pagenum();

		$trans_db_info = $this->load_trans_table($order_by, $order, $per_page, $page_num, $filters);
		$trans_count = $trans_db_info['cnt'];
    $this->items = $trans_db_info['rows'];
	
    $pagination_args = array( 
      'total_items' => $trans_count,
      'per_page' => $per_page,
    );
    $this->set_pagination_args($pagination_args);

    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);
  }

  protected function check_list_get_vars() {
    $order_by = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : '';
    $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : '';

		$filters_list_to_check = array(
			'trans-type' => 'trans_type',
			'order-id' => 'order_id',
			'venue-selection' => 'venue_id',
		);

		$filters = array();
		foreach($filters_list_to_check as $get_name => $arr_name) {
			if (isset($_REQUEST[$get_name]) &&  !is_null($_REQUEST[$get_name])) {
				$filters[$arr_name] = $_REQUEST[$get_name];
			}
		}

		return array( 
			'order_by' => $order_by,
			'order' => $order,
			'filters' => $filters,
		);
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

  protected function load_trans_table($order_by="transaction_date", $order="DESC", $per_page=20, $page_number=1, $filters=array()) {
    global $wpdb;

    $offset = ($page_number - 1) * $per_page;
    $trans_type = isset($filters['trans_type']) ? $filters['trans_type'] : false;
    $venue_id = isset($filters['venue_id']) ? $filters['venue_id'] : false;
    $venue_id = -1 == $venue_id ? false : $venue_id;
    $order_id = isset($filters['order_id']) ? $filters['order_id'] : false;
		$filter_test = '';
		$db_parms = array();
	
    if ($trans_type) {
      $trans_types = array( 
        'order' => '"Order", "Order - From Credit"',
        'redemption' => '"Redemption", "Redemption - From Credit"',
        'creditor_payment' => "Creditor Payment",
        'refund' => "Refund",
        'taste_credit' => "Taste Credit",
        'order_from_credit' => "Order - From Credit",
        'redemption_from_credit' => "Redemption - From Credit",
      );
      $db_trans_type =  $trans_types[$trans_type];
      $filter_test = "WHERE oit.trans_type IN ($db_trans_type)";
    }

		if (false !== $venue_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "oit.venue_id = %d";
			$db_parms[] = $venue_id;
		}
 
		if (false !== $order_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "oit.order_id = %d";
			$db_parms[] = $order_id;
		}
  
    $sql = "
      SELECT *
      FROM {$wpdb->prefix}taste_order_transactions oit
      $filter_test
      ORDER BY oit.$order_by $order
      LIMIT $per_page
      OFFSET $offset;
      ";

    if ($filter_test) {
      $sql = $wpdb->prepare($sql, $db_parms);
    }

    $trans_rows = $wpdb->get_results($sql, ARRAY_A);

		$sql = "
		SELECT COUNT(oit.id)
		FROM {$wpdb->prefix}taste_order_transactions oit
		$filter_test
		";

    if ($filter_test) {
      $sql = $wpdb->prepare($sql, $db_parms);
    }

		$trans_count = $wpdb->get_var($sql);
    
    return array( 
			'rows' => $trans_rows,
			'cnt' => $trans_count,
		);
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
	  $trans_count_by_type = array_column($trans_types_count, 'trans_count', 'trans_type');

    $ret_counts = array(
      'Order' => $trans_count_by_type['Order'] + $trans_count_by_type['Order - From Credit'],
      'Redemption' => $trans_count_by_type['Redemption'] + $trans_count_by_type['Redemption - From Credit'],
      'Payment' => $trans_count_by_type['Creditor Payment'],
      'Refund' => $trans_count_by_type['Refund'],
      'Taste Credit' => $trans_count_by_type['Taste Credit'],
    );
    
    return $ret_counts;
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

	protected function convert_trans_type_to_slug($t_type) {
		$trans_type = str_replace(' - ', '_', $t_type);
		$trans_type = str_replace(' ', '_', $trans_type);
		$trans_type = strtolower($trans_type);
		return $trans_type;
	}

}
/***********************************
 * End of TFTRans_list_table Class
 ***********************************/

function tf_build_trans_admin_list_table() {
	global $tf_trans_table;
  // $tf_trans_table = new TFTRans_list_table();
	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}

  $tf_trans_table->get_columns();
  $tf_trans_table->prepare_items();
  ?>
	<div class="wrap">    
		<h2>Order Transactions</h2>
		<div id="tf_order_trans">			
			<div id="tf_post_body">	
        <?php $tf_trans_table->views() ?>
				<form id="tf-order-trans-form" method="get">	
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />				
					<?php $tf_trans_table->display(); ?>					
				</form>
			</div>			
		</div>
	</div>
	<?php
}

