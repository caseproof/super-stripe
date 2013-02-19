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
    add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    add_action( 'init', array( $this, 'route' ) );
    add_shortcode( 'super-stripe-form', array( $this, 'stripe_form_shortcode' ) );

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

    // register_post_type( 'supstr-form',
                        // array('labels' => array('name' => __('Forms', 'super-stripe'),
                                                // 'singular_name' => __('Form', 'super-stripe'),
                                                // 'add_new_item' => __('Add New Form', 'super-stripe'),
                                                // 'edit_item' => __('Edit Form', 'super-stripe'),
                                                // 'new_item' => __('New Form', 'super-stripe'),
                                                // 'view_item' => __('View Form', 'super-stripe'),
                                                // 'search_items' => __('Search Forms', 'super-stripe'),
                                                // 'not_found' => __('No Forms found', 'super-stripe'),
                                                // 'not_found_in_trash' => __('No Forms found in Trash', 'super-stripe'),
                                                // 'parent_item_colon' => __('Parent Form:', 'super-stripe')
                                                // ),
                              // 'public' => false,
                              // 'show_ui' => true,
                              // 'show_in_menu' => 'super-stripe',
                              // 'capability_type' => 'page',
                              // 'hierarchical' => false,
                              // 'register_meta_box_cb' => 'Supstr::add_meta_boxes',
                              // 'rewrite' => false,
                              // 'supports' => array('title')
                             // )
                      // );
  }
  
  // public static function add_meta_boxes() {
    // add_meta_box("supstr-form-meta", __("Form Options", 'super-stripe'), "Supstr::form_meta_box", 'supstr-form', "normal", "high");
  // }
  
  // public static function form_meta_box() {
    // global $post_id;
    
    // $post_meta = get_post_meta($post_id);
    
    // require(SUPSTR_PATH.'/views/form_meta_box.php');
  // }
  
  public function admin_menu() {
    add_menu_page(__('Super Stripe', 'super-stripe'), __('Super Stripe', 'super-stripe'), 'administrator', 'super-stripe', array($this,'settings_page'));
    add_submenu_page('super-stripe', __('Options', 'super-stripe'), __('Options', 'super-stripe'), 'administrator', 'super-stripe', array($this,'settings_page'));
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
    // 
  }
}

new Supstr();
