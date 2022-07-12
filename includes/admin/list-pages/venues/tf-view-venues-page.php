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
      'member_renewal_date' => "member_renewal_date",
      'membership_cost' => "membership_cost",
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
        return $item[$column_name] ? $item[$column_name] : "N/A";
      case 'paid_member':
        return $item[$column_name] ? 'Y' : '';
      case 'user_registered':
        return explode(' ', $item[$column_name])[0];
      default:
      return $item[$column_name] ? $item[$column_name] : "N/A";
    }
  }

  protected function get_hidden_columns() {
    $hidden_cols = array(
      'description',
      'address1',
      'address2',
      'postcode',
      'state',
      'country',
      'paid_member',
      'member_renewal_date',
      'membership_cost',
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
    return;
    /*
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
    */
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
    );
    return $sort_array;
  }

  protected function get_bulk_actions() {
    $bulk_actions = array(
      'bulk-export' => "Email",
    );
    return $bulk_actions;
  }
 
	protected function column_cb($item) {
		return "<input type='checkbox' name='venues-list-cb' value='{$item['venue_id']}'";
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

    $venue_id = isset($filters['venue_id']) ? $filters['venue_id'] : false;
    $venue_id = -1 == $venue_id ? false : $venue_id;
		$filter_test = '';
		$db_parms = array();
	
    if ($venue_type) {
      $db_venue_type =  ucfirst($venue_type);
      if ("None" == $db_venue_type) {
        $filter_test = "WHERE ven.venue_type IS NULL";
      } else {
        $filter_test = "WHERE ven.venue_type = %s";
        $db_parms[] = $db_venue_type;
      }
    }

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

    $venues_rows = $wpdb->get_results($sql, ARRAY_A);

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
			'rows' => $venues_rows,
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
					<?php $tf_venues_table->display(); ?>					
				</form>
			</div>			
		</div>
	</div>
	<?php
}

