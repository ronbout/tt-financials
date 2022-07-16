<?php
/**
 *  tf-view-payments-page.php 
 *  Sets up the Payments admin page
 *  using Taste_List_Table Class from WP_list_class
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

function tf_view_payments() {
  tf_build_payments_admin_list_table();
}
/******************************
 * TFPayments_list_table Class
 ******************************/
class TFPayments_list_table extends Taste_list_table {

  protected $years;

  public function __construct() {
    parent::__construct(
      array(
        'singular' => "Payment",
        'plural' => "Payments",
        'ajax' => true,
      )
    );
  }

  public function get_columns() {
    $ret_array =  array(
			'cb' => '<input type="checkbox" >',
      'payment_id' => "Payment ID",
			'payment_date' => "Payment Date",
      'amount' => "Payment<br> Amount",
			'venue_id' => "Venue ID",
			'venue_name' => "Venue Name",
      'comment' => "Comment",
      'comment_visible_venues' => "Comment Visible<br> to Venues",
      'attach_vat_invoice' => "Attach Invoice",
      'payment_status' => "Payment Status",
      'invoice' => "View <br>Invoice",
    );

    return $ret_array;
   }

      
   protected function get_financial_columns() {
    $financial_columns = array(
      'pay_gross',
      'comm_val',
      'pay_comm',
      'vat_val',
      'pay_vat',
    );
    return $financial_columns;
   }
   
   protected function column_venue_id($item) {
    $venue_id = $item['venue_id'];
    $cm_link = get_site_url(null, "/campaign-manager/?venue-id={$venue_id}");
    return "<a href='$cm_link' target='_blank'>$venue_id</a>";
   }
      
   protected function column_payment_id($item) {
    if (TASTE_PAYMENT_STATUS_ADJ == $item['payment_status']) {
      return $item['payment_id'];
    }
    $payment_id = $item['payment_id'];
    $cm_link = get_admin_url( null, "admin.php?page=view-order-transactions&payment-id=$payment_id");
    return "<a href='$cm_link' >$payment_id</a>";
   }

  protected function column_default($item, $column_name) {
    switch($column_name) {
      case 'comment_visible_venues':
          return $item[$column_name] ? "Yes" : 'no';
      case 'attach_vat_invoice':
          return $item[$column_name] ? "Yes" : 'no'; 
      case 'invoice':
        if ($item['attach_vat_invoice']) {
          $payment_id = $item['payment_id'];
          $inv_url = plugins_url( "thetaste-venue/pdfs/invoice.php?pay_id=$payment_id" );
          return "
            <a href='$inv_url' target='_blank'>
              <span class='dashicons dashicons-media-document print-invoice-btn'></span>
            </a>
          ";
        } else {
          return "N/A";
        }
        break;
      case 'payment_status':
        return tf_payment_status_to_string($item[$column_name]);
        break; 
      case 'payment_date':
        return explode(' ', $item[$column_name])[0];
      case 'amount':
      case 'venue_id':
      case 'venue_name':
      case 'comment':
        return $item[$column_name] ? $item[$column_name] : "N/A";
      default:
      return $item[$column_name] ? $item[$column_name] : "N/A";
    }
  }

  protected function get_hidden_columns() {
    $hidden_cols = array(
      'attach_vat_invoice',
    );
    
    return $hidden_cols;
  }

  /*
  protected function get_views() {
		$get_string = tf_check_query(false);
    $cur_trans_type = isset($_REQUEST['trans-type']) && $_REQUEST['trans-type'] ? $_REQUEST['trans-type'] : 'all';
		$get_string = remove_query_arg( 'trans-type', $get_string ); 

    $list_link = "admin.php?$get_string";

    $payment_status_counts = $this->count_trans_types();

    $tot_cnt = 0;
    $tmp_views = array();
    foreach ($payment_status_counts as $t_type => $t_cnt) {
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
        <?php $this->months_dropdown() ?>
        <?php $this->years_dropdown() ?>
        <?php $this->custom_dates() ?>
        <input type="submit" name="filter_action" id="trans-list_submit" class="button" value="Filter">
      </div>
      <?php
    }
  }
  */

  protected function months_dropdown() {
    global $wpdb, $wp_locale;

    $m = isset( $_REQUEST['m'] ) ? $_REQUEST['m'] : '';

    $sql = "
        SELECT DISTINCT YEAR( payment_date ) AS year, MONTH( payment_date ) AS month
        FROM {$wpdb->prefix}taste_venue_payment
        ORDER BY payment_date DESC
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
    $yr = isset( $_REQUEST['yr'] ) ? (int) $_REQUEST['yr'] : 0;
    $m = isset( $_REQUEST['m'] ) ? $_REQUEST['m'] : '';
    $yr_options = array_reduce($this->years, function ($ret_options, $year) use ($yr) {
      $ret_options .= "<option value='$year'" . ($year == $yr ? " selected " : "") .  ">$year</option>";
      return $ret_options;
    }, "");
    $style = ("year" != $m) ? "style='display: none;'" : "";
    ?>
    <select name="yr" id="payment-year-select" <?php echo $style ?> >
      <?php echo $yr_options ?>
    </select>
  <?php
  }

  protected function custom_dates() {
    $tmp_dt = date_create();
    $end_date = date_format($tmp_dt, "Y-m-d");
    $tmp_dt = date_add($tmp_dt, date_interval_create_from_date_string("-1 month"));
    $begin_date = date_format($tmp_dt, "Y-m-d");
    $dt1 = isset( $_REQUEST['dt1'] ) ? $_REQUEST['dt1'] : $begin_date;
    $dt2 = isset( $_REQUEST['dt2'] ) ? $_REQUEST['dt2'] : $end_date;
    $m = isset( $_REQUEST['m'] ) ? $_REQUEST['m'] : '';
    $style = ("custom" != $m) ? "style='display: none;'" : "";
    ?>
		<span id="payment-date-range-container" <?php echo $style ?>>
      <input type="text" name="dt1" id="payment-date-start" value="<?php echo $dt1 ?>">
      <span>to</span>
      <input type="text" name="dt2" id="payment-date-end" value="<?php echo $dt2 ?>">
    </span>payment
    <?php
  }

  protected function get_sortable_columns() {
    $sort_array = array(
      'payemnt_id' => array('payemnt_id', true),
      'venue_id' => array('venue_id', true),
      'payment_date' => array('payment_date', true),
      'amount' => array('amount', true), 
      'venue_name' => array('venue_name', true),
      'payment_status' => array('payment_status', true),
      'pay_gross' => array('pay_gross', true),
      'comm_val' => array('comm_val', true),
      'pay_comm' => array('pay_comm', true),
      'vat_val' => array('vat_val', true),
      'pay_vat' => array('pay_vat', true),
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
		return "<input type='checkbox' name='ot-list-cb' value='{$item['payment_id']}'";
	}
  
  public function no_items() {
    echo "No payments found.";
  }
  
  public function prepare_items() {
    $get_vars = $this->check_list_get_vars();
    $order_by = $get_vars['order_by'] ? $get_vars['order_by'] : 'payment_date';
    $order = $get_vars['order'] ? $get_vars['order'] : 'DESC';
		$filters = $get_vars['filters'];
			
    $per_page = $this->get_user_per_page_option();
    $page_num = $this->get_pagenum();

		$payments_db_info = $this->load_payments_table($order_by, $order, $per_page, $page_num, $filters);
		$payments_count = $payments_db_info['cnt'];
    $this->items = $payments_db_info['rows'];
	
    $pagination_args = array( 
      'total_items' => $payments_count,
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
			'payment-id' => 'payment_id',
			'venue-id' => 'venue_id',
      'product-id' => 'product_id',
      'p-status' => 'payment_status',
      's' => 'search',
      'm' => 'date_select',
      'yr' => 'year',
      'dt1' => "date1",
      'dt2' => "date2",
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

  protected function load_payments_table($order_by="payment_date", $order="DESC", $per_page=20, $page_number=1, $filters=array()) {
    global $wpdb;

    $offset = ($page_number - 1) * $per_page;
    $payment_status = isset($filters['payment_status']) ? $filters['payment_status'] : false;
    $venue_id = isset($filters['venue_id']) ? $filters['venue_id'] : false;
    $venue_id = -1 == $venue_id ? false : $venue_id;
    $payment_id = isset($filters['payment_id']) ? $filters['payment_id'] : false;
    $product_id = isset($filters['product_id']) ? $filters['product_id'] : false;
    $date_select = isset($filters['date_select']) ? $filters['date_select'] : false;
    $use_finance_test = false;

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
	
    if (false != $payment_status) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
      $filter_test = " pay.status = %d)";
      $db_parms[] = $payment_status;
    }

		if (false !== $venue_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "pay.venue_id = %d";
			$db_parms[] = $venue_id;
		}

		if (false !== $product_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "pprods.product_id = %d";
			$db_parms[] = $product_id;
		}
 
		if (false !== $payment_id) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
			$filter_test .= "pay.payment_id = %d";
			$db_parms[] = $payment_id;
		} else {
      $financial_columns = $this->get_financial_columns();
      if (in_array($order_by, $financial_columns)) {
        $use_finance_test = true;
      }
    }

    if (false != $search_term) {
      // if numeric, check payment_id, product id, venue id
      // if string, check venue name
			$filter_test .= $filter_test ? " AND " : " WHERE ";
      if (is_numeric($search_term)) {
        $filter_test .= " (pay.payment_id = %d OR pay.venue_id = %d OR pprods.product_id = %d) ";
        $db_parms[] = $search_term; 
        $db_parms[] = $search_term;
        $db_parms[] = $search_term;
      } else {
        $esc_search_term = "%" . $wpdb->esc_like($search_term) . "%";
        $filter_test .= " (ven.name LIKE %s) ";
        $db_parms[] = $esc_search_term;
      }
    } 

		if (false !== $date_select) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
      switch($date_select) {
        case "year":
          $filter_test .= " YEAR(pay.payment_date) = %d ";
          $db_parms[] = $date_year;
          break;
        case "custom":
          $tmp_dt = date_create($date2);
          $tmp_dt = date_add($tmp_dt, date_interval_create_from_date_string("1 day"));
          $end_date = date_format($tmp_dt, "Y-m-d");
          $filter_test .= " (pay.payment_date >= %s AND pay.payment_date < %s) ";
          $db_parms[] = $date1;
          $db_parms[] = $end_date;
          break;
        default:
          $filter_test .= " YEAR(pay.payment_date) = %d AND MONTH(pay.payment_date) = %d";
          $db_parms[] = $date_year;
          $db_parms[] = $date_month;
      }
		}

    $from_where_sql = "
      FROM {$wpdb->prefix}taste_venue_payment_products pprods
      JOIN {$wpdb->prefix}taste_venue_payment pay ON pay.id = pprods.payment_id
      JOIN {$wpdb->prefix}taste_venue ven ON ven.venue_id = pay.venue_id
      JOIN {$wpdb->prefix}taste_venue_products vp ON vp.product_id = pprods.product_id
      LEFT JOIN {$wpdb->prefix}taste_venue_payment_order_item_xref pox ON pox.payment_id = pay.id
      LEFT JOIN {$wpdb->prefix}wc_order_product_lookup plook ON plook.order_item_id = pox.order_item_id
        AND plook.product_id = pprods.product_id
      $filter_test
    ";
  
    $sql = "
      SELECT pay.id AS payment_id, pay.payment_date, pay.amount, 
        pay.venue_id, ven.name as venue_name, pay.comment, pay.comment_visible_venues, 
        pay.attach_vat_invoice, pay.status AS payment_status, pprods.product_id, 
        pprods.amount as product_amount, 
        GROUP_CONCAT(plook.order_item_id) as order_item_ids,
        GROUP_CONCAT(plook.product_qty) as order_item_qty,
        GROUP_CONCAT(plook.order_id) as order_ids
      $from_where_sql
      GROUP BY pprods.product_id";

    if (!$use_finance_test) {
      $sql .= "
      ORDER BY $order_by $order
      LIMIT $per_page
      OFFSET $offset";
    }

    if (count($db_parms)) {
      $sql = $wpdb->prepare($sql, $db_parms);
    }

    // echo "<pre>", $sql, "</pre>";
    
    $payment_rows = $wpdb->get_results($sql, ARRAY_A);
    $payment_rows_w_financials = $this->add_payment_financials($payment_rows);

    
    // if ($use_finance_test) {
    //   $venue_rows_w_financials = $this->sort_select_payments_by_financials($venue_rows_w_financials, $order_by, $order, $per_page, $page_number, $balance_filter);
    //   return $venue_rows_w_financials;
    // }


		$sql = "
		SELECT COUNT(pay.id)
    $from_where_sql
    GROUP BY pay.id
		";

    if (count($db_parms)) {
      $sql = $wpdb->prepare($sql, $db_parms);
    }

		$payments_count = $wpdb->get_var($sql);
    
    return array( 
			'rows' => $payment_rows_w_financials,
			'cnt' => $payments_count,
		);
  }

  protected function count_payments_table() {
    global $wpdb;

    $sql = "
      SELECT COUNT(*)
      FROM {$wpdb->prefix}taste_venue_payment
      ";
  
    $payments_count = $wpdb->get_var($sql);
    
    return $payments_count;
  }

  protected function count_payment_status() {
    global $wpdb;

    $sql = "
      SELECT pay.status, COUNT(*) AS payments_count
      FROM {$wpdb->prefix}taste_venue_payment
      GROUP BY pay.status
      ";
  
    $payment_status_count = $wpdb->get_results($sql, ARRAY_A);
	  $payments_count_by_type = array_column($payment_status_count, 'payments_count', 'status');

    $ret_counts = array(
      'Paid' => $payments_count_by_type[1],
      'Historical' => $payments_count_by_type[2],
      'Pending' => $payments_count_by_type[3],
      'Processing' => $payments_count_by_type[4],
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

	protected function convert_payment_status_to_slug($p_status) {
		return $p_status;
	}

  protected function add_payment_financials($payment_rows) {
    require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-pages/payments/calc_payment_financials.php';
    return $unique_payment_rows;
  }

}
/***********************************
 * End of TFPayments_list_table Class
 ***********************************/

function tf_build_payments_admin_list_table() {
	global $tf_payments_table;
	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}

  $tf_payments_table->get_columns();
  $tf_payments_table->prepare_items();
  ?>
	<div class="wrap">    
		<h2>Creditor Payments</h2>
		<div id="tf_payments">			
			<div id="tf_post_body">	
        <?php $tf_payments_table->views() ?>
				<form id="tf-payments-form" method="get">	
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />				
					<?php 
            $tf_payments_table->search_box("Search Payments", 'tf-payments-search');
            $tf_payments_table->display(); 
          ?>					
				</form>
			</div>			
		</div>
	</div>
	<?php
}

