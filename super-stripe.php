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
    if( is_admin() ) {
      add_action( 'admin_menu', array($this,'admin_menu') );
      //add_action( 'admin_init', 'register_mysettings' );
    } else {
      // non-admin enqueues, actions, and filters
    }
  } 

  public function admin_menu() {
    add_menu_page(__('Super Stripe', 'super-stripe'), __('Super Stripe', 'super-stripe'), 'administrator', 'super-stripe', array($this,'settings_page'), SUPSTR_URL. '/icon.png');
    add_action( 'admin_init', array($this,'register_settings') );
  }

  public function register_settings() {
    register_setting( 'supstr-settings-group', 'supstr_license_key' );
  }

  public function settings_page() {
?>
<div class="wrap">
<h2><?php _e('Super Stripe'); ?></h2>

<form method="post" action="options.php">
  <?php settings_fields( 'supstr-settings-group' ); ?>
  <?php do_settings( 'supstr-settings-group' ); ?>
  <table class="form-table">
    <tr valign="top">
      <th scope="row"><?php _e('Super Stripe License Key'); ?></th>
      <td><input type="text" name="supstr_license_key" value="<?php echo get_option('supstr_license_key'); ?>" /></td>
    </tr>
    </table>
    
    <?php submit_button(); ?>
</form>
</div>
<?php
  }
}

new Supstr();
