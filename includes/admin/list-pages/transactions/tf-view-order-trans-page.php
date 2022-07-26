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

  protected $years;

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
      'order_item_id' => "Order Item<br> ID",
			'transaction_date' => "Transaction Date",
      'trans_type' => "Transaction Type",
      'trans_amount' => "Amount",
			'trans_entry_timestamp' => "Transaction Record<br> Creation Date",
			'batch_id' => "Batch ID",
			'batch_timestamp' => "Batch Date",
			'order_date' => "Order Date",
			'product_id' => "Product<br> ID",
			'product_price' => "Product Price",
			'quantity' => "Item Quantity",
			'gross_revenue' => "Gross Revenue",
      'customer_id' => "Customer<br> ID",
      'customer_name' => "Customer<br >Name",
      'customer_email' => "Customer<br >Email",
			'venue_id' => "Venue ID",
			'venue_name' => "Venue Name",
			'taste_credit_coupon_id' => "Taste Credit<br> Coupon ID",
      'refund_id' => "Refund ID",
      'coupon_id' => "Applied<br> Coupon  ID",
      'coupon_value' => "Applied<br> Coupon Value",
      'net_cost' => "Net Cost",
      'commission' => "Commission",
      'vat' => "VAT",
      'gross_income' => "Gross Income",
      'venue_due' => "Venue Due",
      'payment_id' => "Payment<br> ID",
      'payment_status' => "Payment Status",
      'payment_date' => "Payment Date",
      'redemption_date' => "Redemption Date",
    );

    return $ret_array;
   }
   
   protected function column_payment_id($item) {

    $payment_id = $item['payment_id'];
    if ($payment_id) {
      $link = esc_url(get_admin_url( null, "admin.php?page=view-payments&payment-id=$payment_id"));
      $title = "View Payment $payment_id in the Payments Page";
      $display = "<a title='$title' href='$link' >$payment_id</a>";
      return $this->add_filter_by_action($payment_id, 'payment_id', $display);
    }
    return $payment_id;
   }

   protected function column_venue_id($item) {
    $venue_id = $item['venue_id'];
    $cm_link = esc_url(get_site_url(null, "/campaign-manager/?venue-id={$venue_id}"));
    $title = "Campaign Manager Page for {$item['venue_name']}";
      return "
        <a title='$title' href='$cm_link'>$venue_id</a>
        ";
   }
   
   protected function column_venue_name($item) {
    $venue_id = $item['venue_id'];
    $venue_name = $item['venue_name'];
    $link = esc_url(add_query_arg('venue-id', $venue_id));
    $title = "Filter this page by $venue_name";
      return "
        <a title='$title' href='$link'>$venue_name</a>
        ";
   }

   protected function column_order_item_id($item) {
    $display = $item['order_item_id'];
    return $this->add_filter_by_action($display, 'order_item_id', $display);
   }

  protected function column_default($item, $column_name) {
    switch($column_name) {
      case 'order_id':
      case 'product_id':
        $col_id = $item[$column_name];
        $col_display_name = str_replace('_', ' ', $column_name);
        $col_link = esc_url(get_edit_post_link($col_id));
        $title = "View $col_display_name $col_id in Admin Edit Page";
        $display = "<a title='$title' href='$col_link'>$col_id</a>";
        return $this->add_filter_by_action($col_id, $column_name, $display);
        break;
      case 'id':
      case 'trans_type':
      case 'transaction_date':
      case 'trans_amount':
      case 'order_date':
      case 'net_cost':
      case 'gross_income':
      case 'customer_id':
      case 'customer_name':
      case 'customer_email':
      case 'venue_due':
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
    $cur_trans_type = isset($_REQUEST['trans-type']) && $_REQUEST['trans-type'] ? wp_unslash( $_REQUEST['trans-type']) : 'all';
		$get_string = remove_query_arg( 'trans-type', $get_string ); 

    $list_link = "admin.php?$get_string";

    $trans_types_counts = $this->count_trans_types();

    $tot_cnt = 0;
    $tmp_views = array();
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
        <select name="venue-id" id="trans-list-venue-selection">
					<?php echo $options_list ?>
        </select>
        <?php $this->months_dropdown() ?>
        <?php $this->years_dropdown() ?>
        <?php $this->custom_dates() ?>
        <input type="submit" name="filter_action" id="trans-list_submit" class="button" value="Filter">
      </div>
      <?php
    }
  }

  protected function months_dropdown() {
    global $wpdb, $wp_locale;

    $m = isset( $_REQUEST['m'] ) ? wp_unslash( $_REQUEST['m'] ) : '';

    $sql = "
        SELECT DISTINCT YEAR( transaction_date ) AS year, MONTH( transaction_date ) AS month
        FROM {$wpdb->prefix}taste_order_transactions
        ORDER BY transaction_date DESC
    ";
    
    $year_months = $wpdb->get_results($sql, ARRAY_A);
    $ym_options = "";
    $this->years = array_unique(array_column($year_months, 'year'));

    foreach ( $year_months as $ym ) {
			if ( 0 == $ym['year'] ) {
				continue;
			}

			$month = zeroise( $ym['month'], 2 );
			$year  = $ym['year'];
      $ym_value = $year . $month;

			$ym_options .=	"<option " . ($ym_value == $m ? " selected " : "") . " value='" . esc_attr( $year . $month ) . "'>";
      $ym_options .=  sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year );
      $ym_options .= "</option>";
		}

    echo '
		<select name="m" id="filter-by-date">
			<option value="0" ' . (0 == $m ? " selected " : ""). '>All Dates</option>
      <option value="year" ' . ("year" == $m ? " selected " : ""). '>Year</option>
      <option value="custom" ' . ("custom" == $m ? " selected " : ""). '>Custom Range</option>
      ' . $ym_options . '
    </select>
    ';
  }
  
  protected function years_dropdown() {
    $yr = isset( $_REQUEST['yr'] ) ? (int) wp_unslash( $_REQUEST['yr'] ): 0;
    $m = isset( $_REQUEST['m'] ) ? wp_unslash( $_REQUEST['m']) : '';
    $yr_options = array_reduce($this->years, function ($ret_options, $year) use ($yr) {
      $ret_options .= "<option value='$year'" . ($year == $yr ? " selected " : "") .  ">$year</option>";
      return $ret_options;
    }, "");
    $style = ("year" != $m) ? "style='display: none;'" : "";
    ?>
    <select name="yr" id="list-year-select" <?php echo $style ?> >
      <?php echo $yr_options ?>
    </select>
  <?php
  }

  protected function custom_dates() {
    $tmp_dt = date_create();
    $end_date = date_format($tmp_dt, "Y-m-d");
    $tmp_dt = date_add($tmp_dt, date_interval_create_from_date_string("-1 month"));
    $begin_date = date_format($tmp_dt, "Y-m-d");
    $dt1 = isset( $_REQUEST['dt1'] ) ? wp_unslash( $_REQUEST['dt1']) : $begin_date;
    $dt2 = isset( $_REQUEST['dt2'] ) ? wp_unslash( $_REQUEST['dt2']) : $end_date;
    $m = isset( $_REQUEST['m'] ) ? wp_unslash( $_REQUEST['m']) : '';
    $style = ("custom" != $m) ? "style='display: none;'" : "";
    ?>
		<span id="list-date-range-container" <?php echo $style ?>>
      <input type="date" name="dt1" id="list-date-start" value="<?php echo $dt1 ?>">
      <span>to</span>
      <input type="date" name="dt2" id="list-date-end" value="<?php echo $dt2 ?>">
    </span>
    <?php
  }

  protected function get_sortable_columns() {
    $sort_array = array(
      'order_id' => array('order_id', true),
      'venue_id' => array('venue_id', true),
      'transaction_date' => array('transaction_date', false),
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
      'payment_id' => array('payment_id', true),
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
		return "<input type='checkbox' name='ot-list-cb' value='{$item['id']}'>";
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
    $this->_column_headers = $this->get_column_info();
  }

  protected function check_list_get_vars() {
    $order_by = isset($_REQUEST['orderby']) ? wp_unslash( $_REQUEST['orderby']) : '';
    $order = isset($_REQUEST['order']) ? wp_unslash( $_REQUEST['order']) : '';

		$filters_list_to_check = array(
			'trans-type' => 'trans_type',
			'order-id' => 'order_id',
			'order-item-id' => 'order_item_id',
			'payment-id' => 'payment_id',
      'product-id' => 'product_id',
			'venue-id' => 'venue_id',
      's' => 'search',
      'm' => 'date_select',
      'yr' => 'year',
      'dt1' => "date1",
      'dt2' => "date2",
		);

		$filters = array();
		foreach($filters_list_to_check as $get_name => $arr_name) {
			if (isset($_REQUEST[$get_name]) &&  !is_null($_REQUEST[$get_name])) {
				$filters[$arr_name] = wp_unslash( $_REQUEST[$get_name]);
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
    $order_item_id = isset($filters['order_item_id']) ? $filters['order_item_id'] : false;
    $payment_id = isset($filters['payment_id']) ? $filters['payment_id'] : false;
    $product_id = isset($filters['product_id']) ? $filters['product_id'] : false;
    $date_select = isset($filters['date_select']) ? $filters['date_select'] : false;
    switch($date_select) {
      case "year":
        if (isset($filters['year']) && is_numeric($filters['year'])) {
          $date_year = $filters['year'];
        } else {
          $date_select = false;
        }
        break;
      case "custom":
        if (isset($filters['date1']) && isset($filters['date2'])) {
          $date1 = $filters['date1'];
          $date2 = $filters['date2'];
        } else {
          $date_select = false;
        }
        break;
      case "0":
        $date_select = false;
        break;
      default:
        $date_year = substr($date_select, 0, 4);
        $date_month = substr($date_select, 4, 2);
    }
    $search_term = isset($filters['search']) ? $filters['search'] : false;
		$filter_test = '';
		$db_parms = array();
	
    if ($trans_type) {
      $trans_types = array( 
        'order' => '"Order", "Order - From Credit"',
        'redemption' => '"Redemption", "Redemption - From Credit"',
        'payment' => '"Creditor Payment"',
        'refund' => '"Refund"',
        'taste_credit' => '"Taste Credit"',
        'order_from_credit' => '"Order - From Credit"',
        'redemption_from_credit' => '"Redemption - From Credit"',
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
 
		if (false !== $order_item_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "oit.order_item_id = %d";
			$db_parms[] = $order_item_id;
		}
 
		if (false !== $payment_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "oit.payment_id = %d";
			$db_parms[] = $payment_id;
		}
 
		if (false !== $product_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "oit.product_id = %d";
			$db_parms[] = $product_id;
		}

    if (false != $search_term) {
      // if numeric, check order id, item id, product id, venue id
      // or payment_id
      // if string, check cust name, venue name
			$filter_test .= $filter_test ? " AND " : " WHERE ";
      if (is_numeric($search_term)) {
        $filter_test .= "
          (oit.order_id = %d OR oit.order_item_id = %d OR oit.product_id = %d
            OR oit.venue_id = %d OR OR oit.payment_id = %d
          )
        ";
        $db_parms[] = $search_term; 
        $db_parms[] = $search_term; 
        $db_parms[] = $search_term; 
        $db_parms[] = $search_term; 
        $db_parms[] = $search_term; 
      } else {
        $esc_search_term = "%" . $wpdb->esc_like($search_term) . "%";
        $filter_test .= " (oit.customer_name LIKE %s OR oit.venue_name LIKE %s) ";
        $db_parms[] = $esc_search_term; 
        $db_parms[] = $esc_search_term; 
      }
    } 

		if (false !== $date_select) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
      switch($date_select) {
        case "year":
          $filter_test .= " YEAR(oit.transaction_date) = %d ";
          $db_parms[] = $date_year;
          break;
        case "custom":
          $tmp_dt = date_create($date2);
          $tmp_dt = date_add($tmp_dt, date_interval_create_from_date_string("1 day"));
          $end_date = date_format($tmp_dt, "Y-m-d");
          $filter_test .= " (oit.transaction_date >= %s AND oit.transaction_date < %s) ";
          $db_parms[] = $date1;
          $db_parms[] = $end_date;
          break;
        default:
          $filter_test .= " YEAR(oit.transaction_date) = %d AND MONTH(oit.transaction_date) = %d";
          $db_parms[] = $date_year;
          $db_parms[] = $date_month;
      }
		}
  
    $sql = "
      SELECT *
      FROM {$wpdb->prefix}taste_order_transactions oit
      $filter_test
      ORDER BY oit.$order_by $order
      LIMIT $per_page
      OFFSET $offset;
      ";

    if (count($db_parms)) {
      $sql = $wpdb->prepare($sql, $db_parms);
    }
    
    $trans_rows = $wpdb->get_results($sql, ARRAY_A);

		$sql = "
		SELECT COUNT(oit.id)
		FROM {$wpdb->prefix}taste_order_transactions oit
		$filter_test
		";

    if (count($db_parms)) {
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
  add_thickbox();
	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}

  $tf_trans_table->get_columns();
  $tf_trans_table->prepare_items();
  
  $tmp_dt = date_create();
  $tmp_dt = date_sub($tmp_dt, date_interval_create_from_date_string("14 days"));
  $refresh_date = date_format($tmp_dt, "Y-m-d");
  $cur_page = wp_unslash( $_REQUEST['page']);
  ?>
	<div class="wrap">    
		<h2>Order Transactions</h2>
		<div id="tf_order_trans">		
      <div class="tf_order_trans_update_entry">
        <div><button data-page="<?php echo $cur_page ?>" id="run-build-trans" type="button">Update Transactions Table</button>	</div>
        <label id="update_trans_date_label" for="trans_update_start_date">Refresh Start Date:</label>
        <div><input id="trans_update_start_date" type="date" value="<?php echo $refresh_date ?>"></div>
        <div id="trans-update-spinner" class="spinner"></div>
      </div>	
      <div id="trans-refresh-results" style="display:none;">
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis nesciunt fugiat maiores architecto facilis voluptatem dolore sapiente unde eligendi accusantium!
      </div>
			<div id="tf_post_body">	
        <?php $tf_trans_table->views() ?>
				<form id="tf-order-trans-form" method="get">	
					<input type="hidden" name="page" value="<?php echo $cur_page ?>" />				
					<?php 
            $tf_trans_table->search_box("Search Transactions", 'tf-trans-search');
            $tf_trans_table->display(); 
          ?>					
				</form>
			</div>			
		</div>
	</div>
	<?php
}

