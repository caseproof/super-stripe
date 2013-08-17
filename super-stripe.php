<?php
/*
Plugin Name: Buy Now for Stripe
Plugin URI: http://buynowforstripe.com/
Description: The plugin that makes it easy to accept stripe payments on your website
Version: 1.1.4
Author: Caseproof, LLC
Author URI: http://caseproof.com/
Text Domain: super-stripe
Copyright: 2004-2013, Caseproof, LLC
*/

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

define('SUPSTR_PLUGIN_SLUG',plugin_basename(__FILE__));
define('SUPSTR_PLUGIN_NAME',dirname(SUPSTR_PLUGIN_SLUG));
define('SUPSTR_PATH',WP_PLUGIN_DIR.'/'.SUPSTR_PLUGIN_NAME);
define('SUPSTR_LIB_PATH',SUPSTR_PATH.'/lib');
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

require_once( SUPSTR_LIB_PATH . '/SupstrUpdateController.php' );
require_once( SUPSTR_LIB_PATH . '/SupstrMailchimpController.php');
require_once( SUPSTR_LIB_PATH . '/SupstrTransactionsTable.php' );
require_once( SUPSTR_LIB_PATH . '/SupstrUtils.php' );

SupstrMailchimpController::load_hooks();

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

    add_shortcode('buy-now-form', array($this, 'stripe_form_shortcode'));
    add_shortcode('buy-now-thank-you', array($this, 'stripe_thank_you_shortcode'));
    add_shortcode('buy-now-aws-url', array($this, 'aws_url_shortcode'));
    add_shortcode('buy-now-aws-link', array($this, 'aws_link_shortcode'));

    // These shortcodes are deprecated ... but here for backwards compatibility
    add_shortcode('super-stripe-form', array($this, 'stripe_form_shortcode'));
    add_shortcode('super-stripe-thank-you', array($this, 'stripe_thank_you_shortcode'));
    add_shortcode('super-stripe-aws-url', array($this, 'aws_url_shortcode'));
    add_shortcode('super-stripe-aws-link', array($this, 'aws_link_shortcode'));
    
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
    
    if( isset($post) && $post instanceof WP_Post &&
        ( preg_match('#\[super-stripe-form#', $post->post_content) or
          preg_match('#\[buy-now-form#', $post->post_content) ) ) {
      wp_enqueue_script('supstr-validate-js', SUPSTR_JS_URL.'/jquery.validate.js', array('jquery'));
      wp_enqueue_script('supstr-shortcode-js', SUPSTR_JS_URL.'/shortcode.js', array('jquery'));
      wp_enqueue_script('supstr-aweber-js', SUPSTR_JS_URL.'/aweber.js', array('jquery'));
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
    if(strstr($hook, 'buy-now-txns') !== false) {
      wp_enqueue_script('supstr-list-table-controls-js', SUPSTR_JS_URL.'/table_controls.js', array('jquery'));
      wp_enqueue_style('supstr-list-table-css', SUPSTR_CSS_URL.'/list-table.css');
    }
  }
  
  public function admin_menu() {
    add_menu_page(__('Buy Now', 'super-stripe'), __('Buy Now', 'super-stripe'), 'administrator', 'buy-now-txns', array($this, 'txns_page'));
    add_submenu_page('buy-now-txns', __('Transactions', 'super-stripe'), __('Transactions', 'super-stripe'), 'administrator', 'buy-now-txns', array($this, 'txns_page'));
    add_submenu_page('buy-now-txns', __('Options', 'super-stripe'), __('Options', 'super-stripe'), 'administrator', 'buy-now-options', array($this, 'settings_page'));
  }
  
  public function txns_page() {
    $sub_table = new SupstrTransactionsTable();
    $sub_table->prepare_items();
    
    require SUPSTR_VIEWS_PATH . '/txn_list.php';
  }
  
  public function settings_page() {
    $errors = array();
    $message = '';
    
    if( strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
      if( wp_verify_nonce($_POST['_supstr_nonce'],'super-stripe') ) {
        if( !empty($_POST['supstr_license_key']) and !SupstrUpdateController::is_connected($_POST['supstr_license_key']) ) {
          $errors[] = sprintf(__('API Key must be valid and connected to your <em>Buy Now for Stripe</em> account. %sRead the Instructions%s to see how to do this.', 'super-stripe'), '<a href="http://buynowforstripe.com/docs">', '</a>');
        }
        else {
          update_option('supstr_license_key', $_POST['supstr_license_key']);
          update_option('supstr_aws_access_key', $_POST['supstr_aws_access_key']);
          update_option('supstr_aws_secret_key', $_POST['supstr_aws_secret_key']);
          update_option('supstr_connected', SupstrUpdateController::is_connected($_POST['supstr_license_key']));
          $message = __('Buy Now for Stripe Options Updated Successfully', 'super-stripe');
        }
      }
      else
        $errors[] = __('You creepin bro', 'super-stripe');
    }
    
    $this->display_form($message,$errors);
  }
  
  public function display_form($message = '', $errors = array()) {
    $connected = false;
    if( $license_key = get_option('supstr_license_key') )
      $connected = SupstrUpdateController::is_connected($license_key);
    
    $access_key = get_option('supstr_aws_access_key');
    $secret_key = get_option('supstr_aws_secret_key');

    require(SUPSTR_VIEWS_PATH.'/display_form.php');
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
        // only record the buy now stripe form shortcodes
        if( $m[2][$i] != 'super-stripe-form' and
            $m[2][$i] != 'buy-now-form' )
          continue;

	$atts = shortcode_parse_atts( $atts );
        $args = array_merge( array( 'button' => __('Buy Now', 'super-stripe'),
                                    'show_name' => 'false',
                                    'show_address' => 'false',
                                    'aweber' => 'false',
                                    'aweber_message' => __('Please send me more information about this product.', 'super-stripe'),
                                    'aweber_list' => '',
                                    'mailchimp' => 'false',
                                    'mailchimp_apikey' => '',
                                    'mailchimp_list_id' => '',
                                    'mailchimp_double_optin' => 'true',
                                    'mailchimp_message' => __('Please send me more information about this product.', 'super-stripe'),
                                    'currency' => 'USD' ), $atts );

        // No recurring stuff works in Buy Now for Stripe ... gotta go with MemberPress for that action
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
          $args['message'] = base64_encode($this->default_message());

        $payment_forms[] = $args;
      }

      if( empty($payment_forms) )
        delete_post_meta($post_id,'_supstr_payment_forms');
      else
        update_post_meta($post_id,'_supstr_payment_forms',$payment_forms);
    }
  }

  public function receipt_info($json) {
    ob_start();
    require( SUPSTR_VIEWS_PATH . "/receipt_info.php" );
    return ob_get_clean();
  }

  public function shipping_info($json) {
    if($json->show_name=='true' or $json->show_address=='true') {
      ob_start();
      require( SUPSTR_VIEWS_PATH . "/shipping_info.php" );
      return ob_get_clean();
    }

    return '';
  }

  public function default_message() {
    ob_start();
    require( SUPSTR_VIEWS_PATH . "/default_message.php" );
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
    $show_name = (isset($payment_forms[$form_count]['show_name']) and $payment_forms[$form_count]['show_name']=='true');
    $show_address = (isset($payment_forms[$form_count]['show_address']) and $payment_forms[$form_count]['show_address']=='true');
    $aweber = (isset($payment_forms[$form_count]['aweber']) and $payment_forms[$form_count]['aweber']=='true');
    $aweber_message = $payment_forms[$form_count]['aweber_message'];
    $aweber_list = $payment_forms[$form_count]['aweber_list'];
    $mailchimp = (isset($payment_forms[$form_count]['mailchimp']) and $payment_forms[$form_count]['mailchimp']=='true');
    $mailchimp_message = $payment_forms[$form_count]['mailchimp_message'];
    $mailchimp_apikey = $payment_forms[$form_count]['mailchimp_apikey'];
    $mailchimp_list_id = $payment_forms[$form_count]['mailchimp_list_id'];
    $mailchimp_double_optin = $payment_forms[$form_count]['mailchimp_double_optin'];

    ob_start();
    require(SUPSTR_VIEWS_PATH.'/stripe_form_shortcode.php');
    return ob_get_clean();
  }

  public function aws_url_shortcode( $atts, $content = null ) {
    $access_key = get_option('supstr_aws_access_key');
    $secret_key = get_option('supstr_aws_secret_key');

    if(empty($access_key) or empty($secret_key))
      return '';

    $atts = shortcode_atts(array(
      'bucket' => '',
      'path' => '',
      'expires' => '5:00',
      'maxdownloads' => 0
    ), $atts);

    if( empty($_REQUEST['invoice']) or empty($atts['bucket']) or empty($atts['path']) )
      return '';

    $txn = SupstrUtils::get_txn_by_num($_REQUEST['invoice']);

    if( empty($txn) )
      return '';

    $link_key = base64_encode(md5(json_encode($atts)));
    $links = get_post_meta( $txn->ID, '_supstr_links', true );

    if( empty($links) or !is_array($links) )
      $links = array();

    $links[$link_key] = $atts; 

    update_post_meta( $txn->ID, '_supstr_links', $links );

    if( is_numeric($atts['maxdownloads']) and $atts['maxdownloads'] > 0 ) {
      $downs = get_post_meta( $txn->ID, '_supstr_downs', true );

      if( empty($downs) )
        $downs = array( $link_key => 0 );

      if( !isset($downs[$link_key]) )
        $downs[$link_key] = 0;

      update_post_meta( $txn->ID, '_supstr_downs', $downs );

      return home_url("index.php?plugin=supstr&action=aws_link&i={$_REQUEST['invoice']}&l=".urlencode($link_key));
    }

    $created_at = get_post_meta( $txn->ID, '_supstr_txn_date', true );

    $s3_url = SupstrUtils::el_s3_getTemporaryLink( $access_key,
                                                   $secret_key,
                                                   $atts['bucket'],
                                                   $atts['path'],
                                                   $atts['expires'],
                                                   $created_at );

    return $s3_url;
  }

  public function aws_link_shortcode( $atts, $content = null ) {
    $access_key = get_option('supstr_aws_access_key');
    $secret_key = get_option('supstr_aws_secret_key');

    if(empty($access_key) or empty($secret_key))
      return '';

    if( empty($_REQUEST['invoice']) or empty($atts['bucket']) or empty($atts['path']) )
      return '';

    $s3_url = $this->aws_url_shortcode( $atts, $content );

    $label = isset($atts['label']) ? $atts['label'] : __('Download', 'super-stripe');

    return "<a href=\"{$s3_url}\">{$label}</a>";
  }

  public function stripe_thank_you_shortcode($atts, $content = null) {
    global $wpdb;

    if( isset($_REQUEST['status']) and $_REQUEST['status']=='error' ) {
      return sprintf(__('There was an error processing your payment%s', 'super-stripe'), isset($_REQUEST['error']) ? ": {$_REQUEST['error']}" : '');
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
      else if( $_REQUEST['action']=='aws_link' )
        $this->aws_link_redirect();
      
      exit;
    }
  }
  
  public function process_checkout() {
    if(!isset($_REQUEST['_wpnonce']) or
       !wp_verify_nonce($_REQUEST['_wpnonce'],'Buy Now for Stripe Payment Form')) {
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

    if($args->show_name=='true') {
      $args->first_name = $_REQUEST['supstr_first_name'];
      $args->last_name = $_REQUEST['supstr_last_name'];
    }

    if($args->show_address=='true') {
      $args->address = $_REQUEST['supstr_address'];
      $args->city    = $_REQUEST['supstr_city'];
      $args->state   = $_REQUEST['supstr_state'];
      $args->zip     = $_REQUEST['supstr_zip'];
      $args->country = $_REQUEST['supstr_country'];
    }

    do_action('supstr-process-signup', $args);

    $url = SUPSTR_ENDPOINT . '/checkout/setup';

    $post_args = array( 'method' => 'POST',
                        'timeout' => 45,
                        'sslverify' => false,
                        'blocking' => true,
                        'headers' => array( 'content-type' => 'application/json' ),
                        'body' => json_encode($args) );

    $resp = wp_remote_post( $url, $post_args );

    if( is_wp_error( $resp ) ) {
      _e("Something went wrong: ", 'super-stripe') . $resp->get_error_message();
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

    update_post_meta( $post_id, '_supstr_txn_show_name', $json->show_name=='true' );
    update_post_meta( $post_id, '_supstr_txn_show_address', $json->show_address=='true' );
   
    if( $json->show_name=='true' ) {
      update_post_meta( $post_id, '_supstr_txn_first_name', $json->first_name );
      update_post_meta( $post_id, '_supstr_txn_last_name', $json->last_name );
    }

    if( $json->show_address=='true' ) {
      update_post_meta( $post_id, '_supstr_txn_address', $json->address );
      update_post_meta( $post_id, '_supstr_txn_city', $json->city );
      update_post_meta( $post_id, '_supstr_txn_state', $json->state );
      update_post_meta( $post_id, '_supstr_txn_zip', $json->zip );
      update_post_meta( $post_id, '_supstr_txn_country', $json->country );
    }

    do_action('supstr-transaction-complete', $post_id);

    $main_email = get_option('admin_email');
    $blogname = get_option('blogname');

    $headers = "From: \"{$blogname}\" <{$main_email}>\r\n" .
               "Content-Type: text/html; charset=\"UTF-8\"\r\n\r\n";

    $shipping_info = $this->shipping_info($json);

    if( isset( $json->message ) ) {
      $json_message = base64_decode($json->message);

      $name_a = explode(' ', $json->response->charge->card->name);
      $receipt_info = $this->receipt_info($json);
      $json_message = preg_replace('/\{\$txn_receipt\}/',$receipt_info,$json_message);
      
      $replacements = array( 'first_name' => $name_a[0],
                             'txn_num' => $json->response->charge->id,
                             'txn_date' => date('Y-m-d H:i:s'),
                             'txn_price' => $this->format_currency((float)$json->price),
                             'txn_desc' => $json->description,
                             'txn_email' => $json->email,
                             'txn_shipping_info' => $shipping_info,
                             'txn_buyer_name' => $json->response->charge->card->name,
                             'txn_company' => $json->company );

      $replacements = apply_filters('supstr-customer-email-vars', $replacements);
      $mkvars = create_function('$item', 'return \'{$\'.$item.\'}\';');
      $customer_body = str_replace( array_map( create_function( '$item', 'return \'{$\'.$item.\'}\';'),
                                               array_keys( $replacements ) ),
                                    array_values( $replacements ),
                                    $json_message );

      $customer_body = preg_replace('~\{\$(super-stripe-aws-(url|link)[^\}]*)\}~', '[$1]', $customer_body);
      $customer_body = preg_replace('~\{\$(buy-now-aws-(url|link)[^\}]*)\}~', '[$1]', $customer_body);

      // Artificially set the invoice parameter
      $_REQUEST['invoice'] = $json->response->charge->id;
      $customer_body = do_shortcode($customer_body);
      $customer_body = apply_filters('supstr-customer-email-body', $customer_body);

      update_post_meta( $post_id, '_supstr_txn_replacements', $replacements );
      update_post_meta( $post_id, '_supstr_txn_message', $customer_body );

      wp_mail( $json->email, sprintf(__("** Receipt From %s", 'super-stripe'), $json->company), $customer_body, $headers );
    }

    if( isset( $json->sale_notice_emails ) ) {
      $admin_addrs = explode(',', $json->sale_notice_emails);

      ob_start();
      require(SUPSTR_VIEWS_PATH.'/admin_message.php');
      $admin_body = ob_get_clean();

      foreach( $admin_addrs as $addr )
        wp_mail( $addr, sprintf(__("** New Payment on %s", 'super-stripe'), get_option('blogname')), $admin_body, $headers );
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

  public function format_currency( $number, $show_symbol = true ) {
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
    array_push($buttons, "buynowforstripe_form");
    return $buttons;
  }

  // add the button to the tinyMCE bar
  public function add_tinymce_plugin($plugin_array) {
    $plugin_array['BuyNowForStripe'] = SUPSTR_JS_URL . '/tinymce_form_popup.js';
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

  public function aws_link_redirect() {
    $access_key = get_option('supstr_aws_access_key');
    $secret_key = get_option('supstr_aws_secret_key');

    if(empty($access_key) or empty($secret_key))
      die(__('AWS not setup ...', 'super-stripe'));

    if(!isset($_REQUEST['i']) or !isset($_REQUEST['l']))
      die(__('URL Unavailable ...', 'super-stripe'));
    else {
      $txn = SupstrUtils::get_txn_by_num($_REQUEST['i']);
      $link_key = $_REQUEST['l'];

      if( !empty($txn) ) {
        $links = get_post_meta($txn->ID, '_supstr_links', true );
        $downs = get_post_meta( $txn->ID, '_supstr_downs', true );

        if( empty($links[$link_key]) or
            empty($links[$link_key]['bucket']) or
            empty($links[$link_key]['path']) )
        { die(__('AWS Link not found', 'super-stripe')); }

        if( is_numeric( $downs[$link_key] ) and ( $downs[$link_key] >= $links[$link_key]['maxdownloads'] ) )
          die(__('Unavailable ... Your maximum number of downloads has been reached', 'super-stripe'));

        if( is_numeric($links[$link_key]['maxdownloads']) and $links[$link_key]['maxdownloads'] > 0 ) {
          if( empty($downs) )
            $downs = array( $link_key => 0 );

          if( !isset($downs[$link_key]) )
            $downs[$link_key] = 0;

          $downs[$link_key]++;

          update_post_meta( $txn->ID, '_supstr_downs', $downs );
        }

        $s3_url = SupstrUtils::el_s3_getTemporaryLink( $access_key,
                                                       $secret_key,
                                                       $links[$link_key]['bucket'],
                                                       $links[$link_key]['path'],
                                                       "0:30" );

        wp_redirect( $s3_url );
        die();
      }
      else
        die(__('Invoice not found ... Access to URL is prohibited ...', 'super-stripe'));
    }
  }

} //End class

new Supstr();

