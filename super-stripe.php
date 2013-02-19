<?php
/*
Plugin Name: Super Stripe
Plugin URI: http://www.superstripeapp.com/
Description: The plugin that makes it easy to accept stripe payments on your website
Version: 1.0.0
Author: Caseproof, LLC
Author URI: http://caseproof.com/
Text Domain: super-stripe
Copyright: 2004-2013, Caseproof, LLC
*/

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

define('SUPSTR_PLUGIN_SLUG',plugin_basename(__FILE__));
define('SUPSTR_PLUGIN_NAME',dirname(SUPSTR_PLUGIN_SLUG));
define('SUPSTR_PATH',WP_PLUGIN_DIR.'/'.SUPSTR_PLUGIN_NAME);
define('SUPSTR_URL',plugins_url($path = '/'.SUPSTR_PLUGIN_NAME));
define('SUPSTR_SCRIPT_URL',get_option('home').'/index.php?plugin=supstr');
define('SUPSTR_OPTIONS_SLUG', 'supstr_options');

class Supstr {
  public function __construct() {
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('init', array($this, 'route'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_shortcode('super-stripe-form', array($this, 'stripe_form_shortcode'));
    
    register_post_type( 'supstr-transaction',
                        array('labels' => array('name' => __('Transactions', 'super-stripe'),
                                                'singular_name' => __('Transaction', 'super-stripe'),
                                                'add_new_item' => __('Add New Transaction', 'super-stripe'),
                                                'edit_item' => __('Edit Transaction', 'super-stripe'),
                                                'new_item' => __('New Transaction', 'super-stripe'),
                                                'view_item' => __('View Transaction', 'super-stripe'),
                                                'search_items' => __('Search Transactions', 'super-stripe'),
                                                'not_found' => __('No Transactions found', 'super-stripe'),
                                                'not_found_in_trash' => __('No Transactions found in Trash', 'super-stripe'),
                                                'parent_item_colon' => __('Parent Transaction:', 'super-stripe')
                                                ),
                              'public' => false,
                              'show_ui' => false,
                              'capability_type' => 'post',
                              'hierarchical' => false,
                              'rewrite' => false,
                              'supports' => array()
                             )
                      );
  }
  
  public function enqueue_admin_scripts($hook) {
    if(strstr($hook, 'super-stripe-txns') !== false) {
      wp_enqueue_script('supstr-list-table-controls-js', SUPSTR_URL.'/js/table_controls.js', array('jquery'));
      wp_enqueue_style('supstr-list-table-css', SUPSTR_URL.'/css/list-table.css');
    }
  }
  
  public function admin_menu() {
    add_menu_page(__('Super Stripe', 'super-stripe'), __('Super Stripe', 'super-stripe'), 'administrator', 'super-stripe-txns', array($this, 'txns_page'));
    add_submenu_page('super-stripe-txns', __('Transactions', 'super-stripe'), __('Transactions', 'super-stripe'), 'administrator', 'super-stripe-txns', array($this, 'txns_page'));
    add_submenu_page('super-stripe-txns', __('Options', 'super-stripe'), __('Options', 'super-stripe'), 'administrator', 'super-stripe-options', array($this, 'settings_page'));
  }
  
  public function txns_page() {
    $sub_table = new SupstrTransactionsTable();
    $sub_table->prepare_items();
    
    require SUPSTR_PATH . '/views/txn_list.php';
  }
  
  public function settings_page() {
    $errors = array();
    $message = '';
    
    if( strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
      if( wp_verify_nonce($_POST['_supstr_nonce'],'super-stripe') ) {
        if( empty($_POST['supstr_license_key']) ) {
          $errors[] = __('License Key can\'t be blank.');
        }
        else {
          update_option('supstr_license_key', $_POST['supstr_license_key']);
          $message = __('Super Stripe Options Updated Successfully');
        }
      }
      else
        $errors[] = __('You creepin bro');
    }
    
    $this->display_form($message,$errors);
  }
  
  public function display_form($message = '', $errors = array()) {
    require(SUPSTR_PATH.'/views/display_form.php');
  }
  
  public static function stripe_form_shortcode($atts, $content = null) {
    $content = empty($content) ? __('Buy Now') : $content;
    $license_key = esc_attr(get_option('supstr_license_key'));
    
    if( !$license_key or
        !isset($atts["terms"]) or
        !isset($atts["description"]) or
        !isset($atts["price"]) or
        !isset($atts["return_url"]) or
        !isset($atts["cancel_url"]) )
    { return ''; }
    
    $args = array_merge( array('currency' => 'USD'), $atts );
    
    // No recurring stuff works in Super Stripe ... gotta go with MemberPress for that action
    $args['period'] = 1;
    $args['period_type'] = 'lifetime';
    $args['trial'] = false;
    $args['trial_days'] = 0;
    $args['trial_amount'] = 0.00;
    
    ob_start();
    
    require(SUPSTR_PATH.'/views/stripe_form_shortcode.php');
    
    return ob_get_clean();
  }
  
  public function route() {
    if( isset($_REQUEST['plugin']) and
        $_REQUEST['plugin']=='supstr' and
        isset($_REQUEST['action']) ) {
      
      if( $_REQUEST['action']=='process' )
        $this->process_checkout();
      else if( $_REQUEST['action']=='record' )
        $this->record_checkout();
      
      exit;
    }
  }
  
  public function process_checkout() {
    $license_key = esc_attr(get_option('supstr_license_key'));
    
    if( !$license_key or !isset($_REQUEST['args']) or !isset($_REQUEST['supstr_email']) ) {
      return '';
    }
    
    $args = json_decode( base64_decode( $_REQUEST['args'] ) );
    $args->license_key = $license_key;
    $args->email = $_REQUEST['supstr_email'];
    
    $url = 'http://express.memberpress.com/checkout/setup';
    
    $post_args = array( 'method' => 'POST',
                        'blocking' => true,
                        'headers' => array( 'content-type' => 'application/json' ),
                        'body' => json_encode($args) );
    
    $resp = wp_remote_post( $url, $post_args );
    
    if( is_wp_error( $resp ) ) {
      echo "<pre>";
      _e("Something went wrong: ") . $resp->get_error_message();
      echo "</pre>";
      return;
    }
    
    $json = json_decode($resp['body']); 
    $token = $json->token;
    $url = "http://express.memberpress.com/checkout/{$token}";
    
    wp_redirect( $url );
  }
  
  public function record_checkout() {
    // create supstr_transaction
    // add all the postmeta associated with it
    // See the _meta_key_names I used below in the list_table queries
    // What's stored up here needs to match them
  }
} //End class
new Supstr();

/********* TRANSACTIONS LIST TABLE CLASS *********/
if(!class_exists('WP_List_Table'))
  require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class SupstrTransactionsTable extends WP_List_Table {
  public function __construct()
  {
    parent::__construct(array('singular'=> 'wp_list_supstr_transaction', //Singular label
                              'plural' => 'wp_list_supstr_transactions', //plural label, also this well be one of the table css class
                              'ajax'  => false //We won't support Ajax for this table
                        ));
  }
  
  public function extra_tablenav($which)
  {
    if($which == "top")
      require SUPSTR_PATH."/views/table_controls.php";
  }
  
  public function get_columns()
  {
    return $columns= array( 'col_date' => __('Date', 'super-stripe'),
                            'col_price' => __('Price', 'super-stripe'),
                            'col_txn_num' => __('Transaction #', 'super-stripe'),
                            'col_description' => __('Description', 'super-stripe'),
                            'col_email' => __('Email', 'super-stripe'),
                            'col_buyer_name' => __('Buyer', 'super-stripe')
                          );
  }
  
  public function get_sortable_columns()
  {
    return $sortable = array( 'col_date' => array('date', true),
                              'col_price' => array('price', true),
                              'col_txn_num' => array('txn_num', true),
                              'col_description' => array('description', true),
                              'col_email' => array('email', true),
                              'col_buyer_name' => array('buyer_name', true)
                            );
  }
  
  public function prepare_items()
  {
    $orderby = !empty($_GET["orderby"])?mysql_real_escape_string($_GET["orderby"]):'ID';
    $order = !empty($_GET["order"])?mysql_real_escape_string($_GET["order"]):'DESC';
    $paged = !empty($_GET["paged"])?mysql_real_escape_string($_GET["paged"]):'';
    $perpage = !empty($_GET["perpage"])?mysql_real_escape_string($_GET["perpage"]):10;
    $search = !empty($_GET["search"])?mysql_real_escape_string($_GET["search"]):'';
    
    $list_table = $this->get_txns_table($orderby, $order, $paged, $search, $perpage);
    $totalitems = $list_table['count'];
    
    //How many pages do we have in total?
    $totalpages = ceil($totalitems/$perpage);
    
    /* -- Register the pagination -- */
    $this->set_pagination_args(array( "total_items" => $totalitems,
                                      "total_pages" => $totalpages,
                                      "per_page" => $perpage)
                              );
    
    /* -- Register the Columns -- */
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);
    
    /* -- Fetch the items -- */
    $this->items = $list_table['results'];
  }
  
  public function display_rows()
  {
    //Get the records registered in the prepare_items method
    $records = $this->items;
    
    //Get the columns registered in the get_columns and get_sortable_columns methods
    list($columns, $hidden) = $this->get_column_info();
    
    require SUPSTR_PATH.'/views/txn_row.php';
  }
  
  public function get_txns_table( $order_by = '',
                                  $order = '',
                                  $paged = '',
                                  $search = '',
                                  $perpage = 10 )
  {
    global $wpdb;
    
    $cols = array('ID' => 'txn.ID',
                  'date' => 'txn_date.meta_value',
                  'txn_num' => 'txn_num.meta_value',
                  'price' => 'txn_price.meta_value',
                  'description' => 'txn_desc.meta_value',
                  'email' => 'txn_email.meta_value',
                  'buyer_name' => 'txn_buyer_name.meta_value'
                 );
    
    $args = array("txn.post_type = 'supstr-transaction'");
    
    $joins = array( "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_date ON txn_date.post_id = txn.ID AND txn_date.meta_key = '_supstr_txn_date'",
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_num ON txn_num.post_id = txn.ID AND txn_num.meta_key = '_supstr_txn_number'",
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_price ON txn_price.post_id = txn.ID AND txn_price.meta_key = '_supstr_txn_price'",
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_desc ON txn_desc.post_id = txn.ID AND txn_desc.meta_key = '_supstr_txn_description'",
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_email ON txn_email.post_id = txn.ID AND txn_email.meta_key = '_supstr_txn_email'",
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_buyer_name ON txn_buyer_name.post_id = txn.ID AND txn_buyer_name.meta_key = '_supstr_txn_buyer_name'"
                  );
    
    return $this->list_table($cols, "{$wpdb->posts} AS txn", $joins, $args, $order_by, $order, $paged, $search, $perpage);
  }
  
  public function list_table( $cols,
                              $from,
                              $joins = array(),
                              $args = array(),
                              $order_by = '',
                              $order = '',
                              $paged = '',
                              $search = '',
                              $perpage = 10,
                              $countonly = false ) 
  {
    global $wpdb;
    
    // Setup selects 
    $col_str_array = array();
    foreach($cols as $col => $code)
      $col_str_array[] = "{$code} AS {$col}";
    
    $col_str = implode(", ", $col_str_array);
    
    // Setup Joins
    if(!empty($joins))
      $join_str = " ".implode(" ", $joins);
    
    $args_str = implode(' AND ', $args);
    
    /* -- Ordering parameters -- */
    //Parameters that are going to be used to order the result
    $order_by = (!empty($order_by) and !empty($order))?($order_by = ' ORDER BY '.$order_by.' '.$order):'';
    
    //Page Number
    if(empty($paged) or !is_numeric($paged) or $paged<=0)
      $paged=1;
    
    $limit = '';
    //adjust the query to take pagination into account
    if(!empty($paged) and !empty($perpage))
    {
      $offset=($paged - 1) * $perpage;
      $limit = ' LIMIT '.(int)$offset.','.(int)$perpage;
    }
    
    // Searching
    $search_str = "";
    $searches = array();
    if(!empty($search))
    {
      foreach($cols as $col => $code)
        $searches[] = "{$code} LIKE '%{$search}%'";
        
      if(!empty($searches))
        $search_str = implode(' OR ', $searches);
    }
    
    $conditions = "";
    
    // Pull Searching into where
    if(!empty($args))
    {
      if(!empty($searches))
        $conditions = " WHERE {$args_str} AND ({$search_str})";
      else
        $conditions = " WHERE {$args_str}";
    }
    else
    {
      if(!empty($searches))
        $conditions = " WHERE {$search_str}";
    }
    
    $query = "SELECT {$col_str} FROM {$from}{$join_str}{$conditions}{$order_by}{$limit}";
    $total_query = "SELECT COUNT(*) FROM {$from}{$join_str}{$conditions}";
    $results = $wpdb->get_results($query);
    $count = $wpdb->get_var($total_query);
    
    return array('results' => $results, 'count' => $count);
  }
} //End class
