<?php
/**
 *  tf-view-venues-page.php 
 *  Sets up the venues admin page
 *  using Taste_List_Table Class from WP_list_class
 */
 
defined('ABSPATH') or die('Direct script access disallowed.');

function tf_view_venues() {
  tf_build_venues_admin_list_table();
}
/******************************
 * TFTRans_list_table Class
 ******************************/
class TFVenues_list_table extends Taste_list_table {

  public function __construct() {
    parent::__construct(
      array(
        'singular' => "Venue",
        'plural' => "Venues",
        'ajax' => true,
      )
    );
    $this->set_details_id('venue_id');
  }

  public function get_columns() {
    $ret_array =  array(
			'cb' => '<input type="checkbox" >',
      'venue_id' => "Venue ID",
      'name' => "Venue Name",
      'user_email' => "Venue Email",
      'user_login' => "Login Name",
      'description' => "Description",
      'address1' => "Address 1",
      'address2' => "Address 2",
      'city' => "City",
      'postcode' => "Postcode",
      'state' => "State",
      'country' => "Country",
      'phone' => "Phone",
      'venue_type' => "Venue Type",
      'user_registered' => "Registration<br> Date",
      'voucher_pct' => "Voucher Pct",
      'paid_member' => "Paid Member",
      'member_renewal_date' => "Member<br> Renewal Date",
      'membership_cost' => "Membership<br> Cost",
      'products' => "Products",
      'redeemed_qty' => "Quantity<br> Redeemed",
      'order_cnt' => "Order<br> Count",
      'order_qty' => "Qty<br> Ordered",
      'gross_revenue' => "Gross<br> Revenue",
      'commission' => "Commission",
      'vat' => "VAT",
      'net_payable' => "Net Payable",
      'paid_amount' => "Paid Amount",
      'balance_due' => "Balance Due",
      'actions' => "View Details",
    );

    return $ret_array;
   }
   
   protected function get_financial_columns() {
    $financial_columns = array(
      'products',
      'redeemed_qty',
      'order_cnt',
      'order_qty',
      'gross_revenue',
      'commission',
      'vat',
      'net_payable',
      'paid_amount',
      'balance_due',
    );
    return $financial_columns;
   }

  protected function column_venue_id($item) {
    $venue_id = $item['venue_id'];
    $cm_link = get_site_url(null, "/campaign-manager/?venue-id={$venue_id}");
      return "
        <a href='$cm_link'>$venue_id</a>
        ";
   }
         
	protected function column_actions($item) {
		$venue_id = $item['venue_id'];
		return "
			<span data-id='$venue_id' class='dashicons dashicons-editor-table display-details-btn'></span>
		";
	}

  protected function column_default($item, $column_name) {
    switch($column_name) {
      
      case 'gross_revenue':
      case 'net_payable':
      case 'paid_amount':
      case 'balance_due':
        return isset($item[$column_name]) ? number_format($item[$column_name],2) : "N/A";
      case 'paid_member':
        return $item[$column_name] ? 'Y' : '';
      case 'user_registered':
        return explode(' ', $item[$column_name])[0];
      case 'name':
      case 'user_email':
      case 'user_login':
      case 'description':
      case 'address1':
      case 'address2':
      case 'city':
      case 'postcode':
      case 'state':
      case 'country':
      case 'phone':
      case 'venue_type':
      case 'voucher_pct':
        return isset($item[$column_name]) ? $item[$column_name] : "N/A";
      default:
      return isset($item[$column_name]) ? $item[$column_name] : "N/A";
    }
  }

  public function get_hidden_columns() {
    $hidden_cols = array(
      'description',
      'address1',
      'address2',
      'postcode',
      'state',
      'phone',
      'country',
      'user_login',
      'paid_member',
      'member_renewal_date',
      'membership_cost',
      'user_registered',
      'voucher_pct',
      'redeemed_qty',
      'order_cnt',
      'order_qty',
      'commission',
      'vat',
      'details',
    );
    
    return $hidden_cols;
  }

  protected function get_views() {
		$get_string = tf_check_query(false);
    $cur_venue_type = isset($_REQUEST['venue-type']) && $_REQUEST['venue-type'] ? $_REQUEST['venue-type'] : 'all';
		$get_string = remove_query_arg( 'venue-type', $get_string ); 

    $list_link = "admin.php?$get_string";

    $venue_types_counts = $this->count_venue_types();

    $tot_cnt = 0;
    $tmp_views = array();
    foreach ($venue_types_counts as $v_type => $v_cnt) {
      $tot_cnt += (int) $v_cnt;
      $venue_type = $this->convert_venue_type_to_slug( $v_type);
      $v_cnt = number_format($v_cnt);

      if ($cur_venue_type == $venue_type ) {
        $tmp_views[$venue_type] = "<strong>{$v_type} ($v_cnt)</strong>";
      } else {
        $tmp_views[$venue_type] = "<a href='${list_link}&venue-type=$venue_type'>{$v_type} ($v_cnt)</a>";
      }
    }
    $tot_cnt = number_format($tot_cnt);
    if ("all" == $cur_venue_type) {
      $venue_type_views = array(
        'all' => "<strong>All ($tot_cnt)</strong>"
      );
    } else {
      $venue_type_views = array(
        'all' => "<a href='${list_link}'>All ($tot_cnt)</a>"
      );
    }

    $venue_type_views = array_merge($venue_type_views, $tmp_views);

    return $venue_type_views;
  }

  protected function extra_tablenav($which) {
    if ('top' == $which) {
			$get_vars = $this->check_list_get_vars();
			$filters = $get_vars['filters'];
			$venue_select = isset($filters['venue_id']) ? $filters['venue_id'] : -1;
      $balance_select = isset($filters['balance_due']) ? $filters['balance_due'] : -1;

      $venue_list = $this->get_venue_list();
      $venue_options = "          
        <option value='-1' " . (-1 == $venue_select ? " selected " : "") . ">
       		Select By Venue
        </option>";

      foreach($venue_list as $venue_info) {
        $venue_id = $venue_info['venue_id'];
        $venue_name = $venue_info['name'];
        $venue_options  .= "<option value='$venue_id' " . ($venue_id == $venue_select ? " selected " : "") . ">$venue_name</option> ";
      }

      $balance_options = "
        <option value='-1' " . (-1 == $balance_select ? " selected " : "") . ">
            Select By Balance Due
        </option>
        <option value='positive' " . ('positive' == $balance_select ? " selected " : "") . ">Positive Balance Due</option>
        <option value='zero' " . ('zero' == $balance_select ? " selected " : "") . ">Zero Balance Due</option>
        <option value='non-zero' " . ('non-zero' == $balance_select ? " selected " : "") . ">Non Zero Balance Due</option>
        <option value='negative' " . ('negative' == $balance_select ? " selected " : "") . ">Negative Balance Due</option>
      ";
      ?>
      <div class="alignleft actions">
        <select name="venue-id" id="venues-list-venue-selection">
					<?php echo $venue_options ?>
        </select>
        <select name="balance-due" id="balance-due-venue-selection">
					<?php echo $balance_options ?>
        </select>

        <input type="submit" name="filter_action" id="venues-list_submit" class="button" value="Filter">
      </div>

      <?php
    }
  }

  protected function get_sortable_columns() {
    $sort_array = array(
      'venue_id' => array('venue_id', true),
      'name' => array('name', true),
      'user_email' => array('user_email', true),
      'user_login' => array('user_login', true),
      'description' => array('description', true),
      'city' => array('city', true),
      'postcode' => array('postcode', true),
      'state' => array('state', true),
      'country' => array('country', true),
      'phone' => array('phone', true),
      'venue_type' => array('venue_type', true),
      'user_registered' => array('user_registered', true),
      'voucher_pct' => array('voucher_pct', true),
      'products' => array('products', true),
      'redeemed_qty' => array('redeemed_qty', true),
      'order_cnt' => array('order_cnt', true),
      'order_qty' => array('order_qty', true),
      'gross_revenue' => array('gross_revenue', true),
      'commission' => array('commission', true),
      'vat' => array('vat', true),
      'net_payable' => array('net_payable', true),
      'paid_amount' => array('paid_amount', true),
      'balance_due' => array('balance_due', true),
    );
    return $sort_array;
  }

  protected function get_bulk_actions() {
    $bulk_actions = array(
      'bulk-export' => "Make Payment",
    );
    return $bulk_actions;
  }
 
	protected function column_cb($item) {
		return "<input type='checkbox' name='venues-list-cb' class='venues-list-bulk-cb'  value='{$item['venue_id']}'>";
	}
  
  public function no_items() {
    echo "No Venues found.";
  }
  
  public function prepare_items() {
    $get_vars = $this->check_list_get_vars();
    $order_by = $get_vars['order_by'] ? $get_vars['order_by'] : 'venue_id';
    $order = $get_vars['order'] ? $get_vars['order'] : 'ASC';
		$filters = $get_vars['filters'];
			
    $per_page = $this->get_user_per_page_option();
    $page_num = $this->get_pagenum();

		$venues_db_info = $this->load_venues_table($order_by, $order, $per_page, $page_num, $filters);

		$venues_count = $venues_db_info['cnt'];
    $this->items = $venues_db_info['rows'];
	
    $pagination_args = array( 
      'total_items' => $venues_count,
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
			'venue-type' => 'venue_type',
			'venue-id' => 'venue_id',
      'balance-due' => 'balance_due',
      's' => 'search',
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

  protected function load_venues_table($order_by="venue_id", $order="ASC", $per_page=20, $page_number=1, $filters=array()) {
    global $wpdb;

    $offset = ($page_number - 1) * $per_page;
    $venue_type = isset($filters['venue_type']) ? $filters['venue_type'] : false;
    $search_term = isset($filters['search']) ? $filters['search'] : false;
    $venue_id = isset($filters['venue_id']) ? $filters['venue_id'] : false;
    $venue_id = -1 == $venue_id ? false : $venue_id;
    $use_finance_test = false;
    
    $filter_test = '';
    $db_parms = array();
  
    if ($venue_type) {
			$filter_test .= $filter_test ? " AND " : " WHERE ";
      $db_venue_type =  ucfirst($venue_type);
      if ("None" == $db_venue_type) {
        $filter_test .= " ven.venue_type IS NULL";
      } else {
        $filter_test .= " ven.venue_type = %s";
        $db_parms[] = $db_venue_type;
      }
    }

    if (!$venue_id ) {
      // if a venue is chosen, ignore other filters
      $financial_columns = $this->get_financial_columns();
      $balance_filter = isset($filters['balance_due']) ? $filters['balance_due'] : false;
      if ($balance_filter || in_array($order_by, $financial_columns)) {
        $use_finance_test = true;
      }
    }

    if (false != $search_term) {
      // if numeric, check venue id
      // if string, check name, city
			$filter_test .= $filter_test ? " AND " : " WHERE ";
      if (is_numeric($search_term)) {
        $filter_test .= " ven.venue_id = %d ";
        $db_parms[] = $search_term; 
      } else {
        $esc_search_term = "%" . $wpdb->esc_like($search_term) . "%";
        $filter_test .= " (ven.name LIKE %s OR ven.city LIKE %s) ";
        $db_parms[] = $esc_search_term; 
        $db_parms[] = $esc_search_term; 
      }
    } 
    
    if ($use_finance_test) {
      $sql = "
        SELECT ven.*, u.user_email, u.user_login, u.user_registered
        FROM {$wpdb->prefix}taste_venue ven
        JOIN $wpdb->users u on u.ID = ven.venue_id
        $filter_test
      ";
      
      if (count($db_parms)) {
        $sql = $wpdb->prepare($sql, $db_parms);
      }

    } else {
  
      if (false !== $venue_id) {
        $filter_test .= $filter_test ? " AND " : " WHERE ";
        $filter_test .= "ven.venue_id = %d";
        $db_parms[] = $venue_id;
      }
  
      if (in_array($order_by, array('user_email', 'user_login', 'user_registered'))) {
        $db_order_by = "u.$order_by";
      } else {
        $db_order_by = "ven.$order_by";
      }
    
      $sql = "
        SELECT ven.*, u.user_email, u.user_login, u.user_registered
        FROM {$wpdb->prefix}taste_venue ven
        JOIN $wpdb->users u on u.ID = ven.venue_id
        $filter_test
        ORDER BY $db_order_by $order
        LIMIT $per_page
        OFFSET $offset;
      ";
      
      if (count($db_parms)) {
        $sql = $wpdb->prepare($sql, $db_parms);
      }
    }

    $venues_rows = $wpdb->get_results($sql, ARRAY_A);
    $venue_rows_w_financials = $this->add_venue_financials($venues_rows);

    if ($use_finance_test) {
      $venue_rows_w_financials = $this->sort_select_venues_by_financials($venue_rows_w_financials, $order_by, $order, $per_page, $page_number, $balance_filter);
      $rows = $venue_rows_w_financials['rows'];
      $cnt = $venue_rows_w_financials['cnt'];
      $venue_rows_w_financials_details = $this->add_venue_details($venue_rows_w_financials['rows']);
      return array(
        'rows' => $venue_rows_w_financials_details,
        'cnt' => $cnt,
      );
    }
    
    $venue_rows_w_financials_details = $this->add_venue_details($venue_rows_w_financials);

		$sql = "
		SELECT COUNT(ven.venue_id)
		FROM {$wpdb->prefix}taste_venue ven
		$filter_test
		";

    if (count($db_parms)) {
      $sql = $wpdb->prepare($sql, $db_parms);
    }

		$venues_count = $wpdb->get_var($sql);
    
    return array( 
			'rows' => $venue_rows_w_financials_details,
			'cnt' => $venues_count,
		);
  }

  protected function count_venues_table() {
    global $wpdb;

    $sql = "
      SELECT COUNT(*)
      FROM {$wpdb->prefix}taste_venue
      ";
  
    $venue_count = $wpdb->get_var($sql);
    
    return $venue_count;
  }

  protected function count_venue_types() {
    global $wpdb;

    $sql = "
      SELECT ven.venue_type, COUNT(*) AS venue_type_count
      FROM {$wpdb->prefix}taste_venue ven
      GROUP BY ven.venue_type
      ";
  
    $venue_types_count = $wpdb->get_results($sql, ARRAY_A);
	  $venues_count_by_type = array_column($venue_types_count, 'venue_type_count', 'venue_type');

    $ret_counts = array(
      'Restaurant' => $venues_count_by_type['Restaurant'],
      'Hotel' => $venues_count_by_type['Hotel'],
      'Product' => $venues_count_by_type['Product'],
      'Bar' => $venues_count_by_type['Bar'],
      'None' => $venues_count_by_type[null],
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

	protected function convert_venue_type_to_slug($v_type) {
		$venue_type = strtolower($v_type);
		return $venue_type;
	}

  protected function add_venue_financials($venue_rows) {
    require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-pages/venues/calc_venue_financials.php';
    return $venue_return_rows;
  }
  
  protected function add_venue_details($venue_rows) {
    require_once TFINANCIAL_PLUGIN_INCLUDES.'/admin/list-pages/venues/calc_venue_details.php';
    return $venue_rows;
  }

  protected function sort_select_venues_by_financials($venue_rows_w_financials, $order_by, $order, $per_page, $page_number, $balance_filter) {
    $tmp_rows = $venue_rows_w_financials;
    if ($balance_filter) {
      switch($balance_filter) {
        case 'positive':
          $tmp_rows = array_filter($tmp_rows, function ($tmp_row)  {
            return round($tmp_row['balance_due'], 2) > 0;
          } );
          break;
        case 'negative':
          $tmp_rows = array_filter($tmp_rows, function ($tmp_row)  {
            return round($tmp_row['balance_due'], 2) < 0;
          } );
          break;
        case 'zero':
          $tmp_rows = array_filter($tmp_rows, function ($tmp_row)  {
            return round($tmp_row['balance_due'], 2) == 0;
          } );
          break;
          break;
        case 'non-zero':
          $tmp_rows = array_filter($tmp_rows, function ($tmp_row)  {
            return round($tmp_row['balance_due'], 2) <> 0;
          } );
          break;
      }
    }
    
    $cnt = count($tmp_rows);
    if ($order_by) {
      $sort_dir = "desc" == strtolower($order) ? SORT_DESC : SORT_ASC;
      $sort_column = array_column($tmp_rows, $order_by);
      array_multisort($sort_column, $sort_dir, $tmp_rows);
    }
    
    $offset = ($page_number - 1) * $per_page;
    $tmp_rows = array_slice($tmp_rows, $offset, $per_page);

    return array(
			'rows' => $tmp_rows,
			'cnt' => $cnt,
    );
  }

}
/***********************************
 * End of TFVenues_list_table Class
 ***********************************/

function tf_build_venues_admin_list_table() {
	global $tf_venues_table;
	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}

  $tf_venues_table->get_columns();
  $tf_venues_table->prepare_items();
  ?>
	<div class="wrap">    
		<h2>Venues</h2>
		<div id="tf_venues">			
			<div id="tf_post_body">	
        <?php $tf_venues_table->views() ?>
				<form id="tf-venues-form" method="get">	
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />				
					<?php 
            $tf_venues_table->search_box("Search Venues", 'tf-venues-search');
            $tf_venues_table->display(); 
          ?>					
				</form>
			</div>			
		</div>
	</div>
	<?php
}

