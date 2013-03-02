<?php
/*
Plugin Name: Super Stripe
Plugin URI: http://www.superstripeapp.com/
Description: The plugin that makes it easy to accept stripe payments on your website
Version: 1.1.1
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
define('SUPSTR_JS_URL',SUPSTR_URL.'/js');
define('SUPSTR_CSS_URL',SUPSTR_URL.'/css');
define('SUPSTR_VIEWS_PATH',SUPSTR_PATH.'/views');
define('SUPSTR_SCRIPT_URL',get_option('home').'/index.php?plugin=supstr');
define('SUPSTR_OPTIONS_SLUG', 'supstr_options');

if(defined('SUPSTR_CUSTOM_ENDPOINT'))
  define('SUPSTR_ENDPOINT', SUPSTR_CUSTOM_ENDPOINT); 
else
  define('SUPSTR_ENDPOINT', 'https://secure.superstripeapp.com'); 

require_once( SUPSTR_PATH . '/SupstrUpdateController.php' );

class Supstr {
  public function __construct() {
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_notices', array($this, 'connect_warning'));
    add_action('init', array($this, 'route'));
    add_action('init', array($this, 'add_shortcode_buttons'));
    add_action('wp_ajax_supstr_shortcode_form', array($this, 'display_shortcode_form'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('save_post', array($this, 'compile_shortcode'));
    add_shortcode('super-stripe-form', array($this, 'stripe_form_shortcode'));
    add_shortcode('super-stripe-thank-you', array($this, 'stripe_thank_you_shortcode'));
    
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
  
  public function enqueue_front_scripts() {
    global $post;
    
    if(isset($post) && $post instanceof WP_Post && preg_match('#\[super-stripe-form#', $post->post_content)) {
      wp_enqueue_script('supstr-validate-js', SUPSTR_JS_URL.'/jquery.validate.js', array('jquery'));
      wp_enqueue_script('supstr-shortcode-js', SUPSTR_JS_URL.'/shortcode.js', array('jquery'));
    }
  }

  public function connect_warning() {
    $connected = get_option('supstr_connected'); 
    if(isset($_POST['supstr_license_key']))
      $license_key = $_POST['supstr_license_key'];
    else
      $license_key = get_option('supstr_license_key');

    if( empty($license_key) ) {
      require SUPSTR_VIEWS_PATH . '/setup_headline.php';
      update_option('supstr_connected',false); 
    }
    else if( !$connected and !SupstrUpdateController::is_connected($license_key) ) {
      require SUPSTR_VIEWS_PATH . '/setup_headline.php';
      update_option('supstr_connected',false); 
    }
  }
  
  public function enqueue_admin_scripts($hook) {
    if(strstr($hook, 'super-stripe-txns') !== false) {
      wp_enqueue_script('supstr-list-table-controls-js', SUPSTR_JS_URL.'/table_controls.js', array('jquery'));
      wp_enqueue_style('supstr-list-table-css', SUPSTR_CSS_URL.'/list-table.css');
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
        if( !empty($_POST['supstr_license_key']) and !SupstrUpdateController::is_connected($_POST['supstr_license_key']) ) {
          $errors[] = sprintf(__('API Key must be valid and connected to your Super Stripe account. %sRead the Instructions%s to see how to do this.'), '<a href="http://superstripeapp.com/docs">', '</a>');
        }
        else {
          update_option('supstr_license_key', $_POST['supstr_license_key']);
          update_option('supstr_connected', SupstrUpdateController::is_connected($_POST['supstr_license_key']));
          $message = __('Super Stripe Options Updated Successfully');
        }
      }
      else
        $errors[] = __('You creepin bro');
    }
    
    $this->display_form($message,$errors);
  }
  
  public function display_form($message = '', $errors = array()) {
    $connected = false;
    if( $license_key = get_option('supstr_license_key') )
      $connected = SupstrUpdateController::is_connected($license_key);

    require(SUPSTR_PATH.'/views/display_form.php');
  }

  // Saves the shortcode info to the database so we don't have 
  // to make it available on the page where private info can be exposed
  public function compile_shortcode($post_id) {
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      return $post_id;
    
    if(defined('DOING_AJAX'))
      return;

    //verify post is not a revision
    if ( !wp_is_post_revision( $post_id ) ) {
      $post = get_post($post_id);
      $payment_forms = array();
      $pattern = get_shortcode_regex();
      preg_match_all("/$pattern/s", $post->post_content, $m);

      foreach($m[3] as $i => $atts) {
        // only record the super stripe form shortcodes
        if( $m[2][$i] != 'super-stripe-form' )
          continue;

	$atts = shortcode_parse_atts( $atts );
        $args = array_merge( array('button' => __('Buy Now'), 'currency' => 'USD'), $atts );

        // No recurring stuff works in Super Stripe ... gotta go with MemberPress for that action
        $args['period'] = 1;
        $args['period_type'] = 'lifetime';
        $args['trial'] = false;
        $args['trial_days'] = 0;
        $args['trial_amount'] = 0.00;

        $args["return_url"] = home_url('index.php?url=' . base64_encode( $args['return_url'] ) . '&plugin=supstr&action=record');
        $args["cancel_url"] = home_url('index.php?url=' . base64_encode( $args['cancel_url'] ) . '&plugin=supstr&action=cancel');
        $args['livemode'] = (bool)( isset( $atts['livemode'] ) ? ( $atts['livemode'] == 'true' ) : false );
        if( isset( $m[5][$i] ) and !empty( $m[5][$i] ) )
          $args['message'] = base64_encode(wpautop($m[5][$i]));
        else
          $args['message'] = base64_encode(self::default_message());

        $payment_forms[] = $args;
      }

      if( empty($payment_forms) )
        delete_post_meta($post_id,'_supstr_payment_forms');
      else
        update_post_meta($post_id,'_supstr_payment_forms',$payment_forms);
    }
  }

  public static function receipt_info() {
    ob_start();
    ?>
    <table>
      <tr>
        <td><b><?php _e('Name:'); ?></b></td>
        <td>{$txn_buyer_name}</td>
      </tr>
      <tr>
        <td><b><?php _e('Price:'); ?></b></td>
        <td>{$txn_price}</td>
      </tr>
      <tr>
        <td><b><?php _e('Description:'); ?></b></td>
        <td>{$txn_desc}</td>
      </tr>
      <tr>
        <td><b><?php _e('Payee:'); ?></b></td>
        <td>{$txn_company}</td>
      </tr>
      <tr>
        <td><b><?php _e('Invoice:'); ?></b></td>
        <td>{$txn_num}</td>
      </tr>
      <tr>
        <td><b><?php _e('Email:'); ?></b></td>
        <td>{$txn_email}</td>
      </tr>
    </table>
    <?php
    return ob_get_clean();
  }

  public static function default_message() {
    ob_start();
    ?>
    <p><?php _e('Dear {$first_name},'); ?></p>
    <p><?php printf(__('Thank you for your purchase on %s. Keep this email for your records:'), get_option('blogname')); ?></p>
    <br/>
    {$txn_receipt}
    <br/>
    <p><?php _e('Cheers,'); ?><br/><br/>
       <?php printf(__('The %s Team'), get_option('blogname')); ?></p>
    <?php

    return ob_get_clean();
  }
  
  public function stripe_form_shortcode($atts, $content = null) {
    global $post;
    static $form_count;

    if(!isset($form_count))
      $form_count=0;
    else
      $form_count++;

    $license_key = esc_attr(get_option('supstr_license_key'));

    if( !$license_key or
        !isset($atts["terms"]) or
        !isset($atts["description"]) or
        !isset($atts["price"]) or
        !isset($atts["return_url"]) or
        !isset($atts["cancel_url"]) )
    { return ''; }

    $payment_forms = get_post_meta($post->ID,'_supstr_payment_forms',true);

    if( !isset($payment_forms[$form_count]) )
      return '';

    $button = $payment_forms[$form_count]['button'];

    ob_start();
    require(SUPSTR_PATH.'/views/stripe_form_shortcode.php');
    return ob_get_clean();
  }

  public static function stripe_thank_you_shortcode($atts, $content = null) {
    global $wpdb;

    if( isset($_REQUEST['status']) and $_REQUEST['status']=='error' ) {
      return sprintf(__('There was an error processing your payment%s'), isset($_REQUEST['error']) ? ": {$_REQUEST['error']}" : '');
    }

    if( !isset($_REQUEST['invoice']) or !isset($_REQUEST['token']) or
        empty($_REQUEST['invoice']) or empty($_REQUEST['token']) )
      return '';

    $query = "SELECT p.ID FROM {$wpdb->posts} AS p " .
               "JOIN {$wpdb->postmeta} AS txn_num_pm " .
                 "ON txn_num_pm.post_id=p.ID " .
                "AND txn_num_pm.meta_key='_supstr_txn_num' " .
               "JOIN {$wpdb->postmeta} AS txn_token_pm " .
                 "ON txn_token_pm.post_id=p.ID " .
                "AND txn_token_pm.meta_key='_supstr_txn_token' " .
              "WHERE post_type='supstr-transaction' " .
                "AND txn_num_pm.meta_value=%s " .
                "AND txn_token_pm.meta_value=%s";

    $query = $wpdb->prepare( $query, $_REQUEST['invoice'], $_REQUEST['token'] );
    $post_id = $wpdb->get_var( $query );

    if( empty($post_id) ) { return ''; }

    return get_post_meta( $post_id, '_supstr_txn_message', true );
  }

  public function route() {
    if( isset($_REQUEST['plugin']) and
        $_REQUEST['plugin']=='supstr' and
        isset($_REQUEST['action']) ) {
      
      if( $_REQUEST['action']=='process' )
        $this->process_checkout();
      else if( $_REQUEST['action']=='record' )
        $this->record_checkout();
      else if( $_REQUEST['action']=='cancel' )
        $this->cancel_checkout();
      
      exit;
    }
  }
  
  public function process_checkout() {
    if(!isset($_REQUEST['_wpnonce']) or
       !wp_verify_nonce($_REQUEST['_wpnonce'],'Super Stripe Payment Form')) {
      die();
    }

    $license_key = esc_attr(get_option('supstr_license_key'));

    if( !$license_key or !isset($_REQUEST['args']) or !isset($_REQUEST['supstr_email']) )
      return '';

    if( !isset($_REQUEST['pid']) )
      return '';

    $post = get_post($_REQUEST['pid']);

    $payment_forms = get_post_meta($post->ID,'_supstr_payment_forms',true);

    if( !isset($payment_forms[$_REQUEST['args']]) )
      return '';

    $args = (object)$payment_forms[$_REQUEST['args']];
    $args->license_key = $license_key;
    $args->email = $_REQUEST['supstr_email'];
    
    $url = SUPSTR_ENDPOINT . '/checkout/setup';
    
    $post_args = array( 'method' => 'POST',
                        'timeout' => 45,
                        'sslverify' => false,
                        'blocking' => true,
                        'headers' => array( 'content-type' => 'application/json' ),
                        'body' => json_encode($args) );
    
    $resp = wp_remote_post( $url, $post_args );
    
    if( is_wp_error( $resp ) ) {
      _e("Something went wrong: ") . $resp->get_error_message();
      return;
    }
    
    $json = json_decode($resp['body']); 
    $token = $json->token;
    $url = SUPSTR_ENDPOINT . "/checkout/{$token}";
    
    wp_redirect( $url );
  }
  
  public function record_checkout() {
    if( $_REQUEST['status'] == 'error' ) {
      $uri = base64_decode($_REQUEST['url']);
      $delim = preg_match( '/\?/', $uri ) ? '&' : '?';

      $error = isset($_REQUEST['error']) ? "&error=" . urlencode($_REQUEST['error']) : "";

      wp_redirect( $uri . $delim . 'token=' . $_REQUEST['token'] . '&status=error' . $error );
      exit;
    }

    $license_key = esc_attr(get_option('supstr_license_key'));
    $url = SUPSTR_ENDPOINT . "/checkout/info/{$_REQUEST['token']}/{$license_key}";

    $get_args = array( 'method' => 'GET',
                       'timeout' => 45,
                       'blocking' => true,
                       'sslverify' => false,
                       'headers' => array( 'content-type' => 'application/json' ),
                       'body' => '' );

    $resp = wp_remote_get( $url, $get_args );

    $json = json_decode( $resp['body'] );

    if( isset($json->response->error) ) {
      $uri = base64_decode($_REQUEST['url']);
      $delim = preg_match( '/\?/', $uri ) ? '&' : '?';

      wp_redirect( $uri . $delim . 'token=' . $_REQUEST['token'] . '&status=error&error=' . urlencode($json->response->error) );
      exit;
    }

    $post = array( 'post_status' => 'publish', 'post_type' => 'supstr-transaction' );

    $post_id = wp_insert_post( $post );

    update_post_meta( $post_id, '_supstr_txn_date', date('Y-m-d H:i:s') );
    update_post_meta( $post_id, '_supstr_txn_num', $json->response->charge->id );
    update_post_meta( $post_id, '_supstr_txn_token', $_REQUEST['token'] );
    update_post_meta( $post_id, '_supstr_txn_price', $json->price );
    update_post_meta( $post_id, '_supstr_txn_desc', $json->description );
    update_post_meta( $post_id, '_supstr_txn_email', $json->email );
    update_post_meta( $post_id, '_supstr_txn_currency', $json->currency );
    update_post_meta( $post_id, '_supstr_txn_buyer_name', $json->response->charge->card->name );
    update_post_meta( $post_id, '_supstr_txn_response', $json );

    do_action('supstr-transaction-complete', $post_id);

    $main_email = get_option('admin_email');
    $blogname = get_option('blogname');

    $headers = "From: \"{$blogname}\" <{$main_email}>\r\n" .
               "Content-Type: text/html; charset=\"UTF-8\"\r\n\r\n";

    if( isset( $json->message ) ) {
      $json_message = base64_decode($json->message);

      $name_a = explode(' ', $json->response->charge->card->name);
      $receipt_info = self::receipt_info();
      $json_message = preg_replace('/\{\$txn_receipt\}/',$receipt_info,$json_message);
      
      $replacements = array( 'first_name' => $name_a[0],
                             'txn_num' => $json->response->charge->id,
                             'txn_date' => date('Y-m-d H:i:s'),
                             'txn_price' => self::format_currency((float)$json->price),
                             'txn_desc' => $json->description,
                             'txn_email' => $json->email,
                             'txn_buyer_name' => $json->response->charge->card->name,
                             'txn_company' => $json->company );

      $replacements = apply_filters('supstr-customer-email-vars', $replacements);
      $mkvars = create_function('$item', 'return \'{$\'.$item.\'}\';');
      $customer_body = str_replace( array_map( create_function( '$item', 'return \'{$\'.$item.\'}\';'),
                                               array_keys( $replacements ) ),
                                    array_values( $replacements ),
                                    $json_message );

      $customer_body = apply_filters('supstr-customer-email-body', $customer_body);

      update_post_meta( $post_id, '_supstr_txn_replacements', $replacements );
      update_post_meta( $post_id, '_supstr_txn_message', $customer_body );

      wp_mail( $json->email, sprintf(__("** Receipt From %s"), $json->company), $customer_body, $headers );
    }

    if( isset( $json->sale_notice_emails ) ) {
      $admin_addrs = explode(',', $json->sale_notice_emails);

      ob_start();
      ?>
      <p><?php printf(__('A transaction on %s just completed successfully:'), get_option('blogname')); ?></p>
      <table>
        <tr>
          <td><b><?php _e('Name:'); ?></b></td>
          <td><?php echo $json->response->charge->card->name; ?></td>
        </tr>
        <tr>
          <td><b><?php _e('Price:'); ?></b></td>
          <td><?php echo Supstr::format_currency($json->price); ?></td>
        </tr>
        <tr>
          <td><b><?php _e('Description:'); ?></b></td>
          <td><?php echo $json->description; ?></td>
        </tr>
        <tr>
          <td><b><?php _e('Email:'); ?></b></td>
          <td><?php echo $json->email; ?></td>
        </tr>
        <tr>
          <td><b><?php _e('Invoice:'); ?></b></td>
          <td><?php echo $json->response->charge->id; ?></td>
        </tr>
        <tr>
          <td><b><?php _e('Payee:'); ?></b></td>
          <td><?php echo $json->company; ?></td>
        </tr>
      </table>
      <?php

      $admin_body = ob_get_contents();
      ob_end_clean();

      foreach( $admin_addrs as $addr )
        wp_mail( $addr, sprintf(__("** New Payment on %s"), get_option('blogname')), $admin_body, $headers );
    }

    $uri = base64_decode($_REQUEST['url']);
    $delim = preg_match( '/\?/', $uri ) ? '&' : '?';

    wp_redirect( $uri . $delim . 'token=' . $_REQUEST['token'] . '&invoice=' . $json->response->charge->id );
    exit;
  }

  public function cancel_checkout() {
    if(isset($_REQUEST['url'])) {
      $uri = base64_decode($_REQUEST['url']);
      $delim = preg_match( '/\?/', $uri ) ? '&' : '?';

      wp_redirect( $uri . $delim . 'token=' . $_REQUEST['token'] );
    }

    // Send cancel email
    exit;
  }

  public static function format_currency( $number, $show_symbol = true ) {
    global $wp_locale;

    // Goin out on a limb here but since Stripe is currently
    // only available in USA & Canada I think the bulk of 
    // people will be looking at this as their currency symbol
    $symbol = '$';

    $rstr = (string)number_format((float)$number, 2, $wp_locale->number_format['decimal_point'], '');

    if($show_symbol) { $rstr = $symbol . $rstr; }

    return $rstr;
  }

  // registers the buttons for use
  public function register_buttons($buttons) {
    array_push($buttons, "superstripe_form");
    return $buttons;
  }

  // add the button to the tinyMCE bar
  public function add_tinymce_plugin($plugin_array) {
    $plugin_array['SuperStripe'] = SUPSTR_JS_URL . '/tinymce_form_popup.js';
    return $plugin_array;
  }

  // filters the tinyMCE buttons and adds our custom buttons
  public function add_shortcode_buttons() {
    // Don't bother doing this stuff if the current user lacks permissions
    if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
      return;
  
    // Add only in Rich Editor mode
    if ( get_user_option('rich_editing') == 'true') {
      // filter the tinyMCE buttons and add our own
      add_filter("mce_external_plugins", array($this,"add_tinymce_plugin"));
      add_filter('mce_buttons', array($this,'register_buttons'));
    }
  }

  public function display_shortcode_form() {
    require( SUPSTR_VIEWS_PATH . '/tinymce_form_popup.php' );
    exit;
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
