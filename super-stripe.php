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
  } 

  public function admin_menu() {
    add_options_page(__('Super Stripe', 'super-stripe'), __('Super Stripe', 'super-stripe'), 'administrator', 'super-stripe', array($this,'settings_page'));
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
      else {
        $errors[] = __('You creepin bro');
      }
    }

    $this->display_form($message,$errors);
  }

  public function display_form($message='',$errors=array()) {
    ?>
    <div class="wrap">
      <h2><?php _e('Super Stripe'); ?></h2>
      <?php require( SUPSTR_PATH . '/errors.php' ); ?>
      <form method="post" action="">
        <?php wp_nonce_field('super-stripe','_supstr_nonce'); ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php _e('License Key'); ?></th>
            <td><input type="text" name="supstr_license_key" value="<?php echo esc_attr(get_option('supstr_license_key')); ?>" /></td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  public static function stripe_form_shortcode($atts, $content=null) {
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
    ?>
    <form action="<?php echo home_url("index.php"); ?>" method="post">
      <input type="hidden" name="plugin" value="supstr" />
      <input type="hidden" name="action" value="process" />
      <input type="hidden" name="args" value="<?php echo base64_encode(json_encode($args)); ?>" />
      <div class="super-stripe-form">
        <label for="supstr_email"><?php _e('Email Address:'); ?>
        <input type="text" name="supstr_email" value="<?php echo isset($_REQUEST['supstr_email']) ? $_REQUEST['supstr_email'] : ''; ?>" /></label><br/>
        <input type="submit" name="checkout" value="<?php echo $content; ?>" />
      </div>
    </form>
    <?php

    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  public function route() {
    if( isset($_REQUEST['plugin']) and $_REQUEST['plugin']=='supstr' ) {
      if( isset($_REQUEST['action']) and $_REQUEST['action']=='process' ) {
        $this->process_checkout();
      }
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
}

new Supstr();
