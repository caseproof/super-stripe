<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

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
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_num ON txn_num.post_id = txn.ID AND txn_num.meta_key = '_supstr_txn_num'",
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_price ON txn_price.post_id = txn.ID AND txn_price.meta_key = '_supstr_txn_price'",
                    "LEFT OUTER JOIN {$wpdb->postmeta} AS txn_desc ON txn_desc.post_id = txn.ID AND txn_desc.meta_key = '_supstr_txn_desc'",
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
