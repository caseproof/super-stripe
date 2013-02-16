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
define('SUPSTR_IMAGES_PATH',SUPSTR_PATH.'/images');
define('SUPSTR_CSS_PATH',SUPSTR_PATH.'/css');
define('SUPSTR_JS_PATH',SUPSTR_PATH.'/js');
define('SUPSTR_I18N_PATH',SUPSTR_PATH.'/i18n');
define('SUPSTR_LIB_PATH',SUPSTR_PATH.'/app/lib');
define('SUPSTR_APIS_PATH',SUPSTR_PATH.'/app/apis');
define('SUPSTR_MODELS_PATH',SUPSTR_PATH.'/app/models');
define('SUPSTR_CONTROLLERS_PATH',SUPSTR_PATH.'/app/controllers');
define('SUPSTR_GATEWAYS_PATH',SUPSTR_PATH.'/app/gateways');
define('SUPSTR_VIEWS_PATH',SUPSTR_PATH.'/app/views');
define('SUPSTR_WIDGETS_PATH',SUPSTR_PATH.'/app/widgets');
define('SUPSTR_HELPERS_PATH',SUPSTR_PATH.'/app/helpers');
define('SUPSTR_URL',plugins_url($path = '/'.SUPSTR_PLUGIN_NAME));
define('SUPSTR_IMAGES_URL',SUPSTR_URL.'/images');
define('SUPSTR_CSS_URL',SUPSTR_URL.'/css');
define('SUPSTR_JS_URL',SUPSTR_URL.'/js');
define('SUPSTR_GATEWAYS_URL',SUPSTR_URL.'/app/gateways');
define('SUPSTR_SCRIPT_URL',get_option('home').'/index.php?plugin=supstr');
define('SUPSTR_OPTIONS_SLUG', 'supstr_options');
define('SUPSTR_EDITION', '!!supercleanse!!');

/**
 * Returns current plugin version.
 *
 * @return string Plugin version
 */
function supstr_plugin_info($field) {
  static $plugin_folder, $plugin_file;

  if( !isset($plugin_folder) or !isset($plugin_file) ) {
    if( ! function_exists( 'get_plugins' ) )
      require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );
  }

  if(isset($plugin_folder[$plugin_file][$field]))
    return $plugin_folder[$plugin_file][$field];

  return '';
}

// Plugin Information from the plugin header declaration
define('SUPSTR_VERSION', supstr_plugin_info('Version'));
define('SUPSTR_DISPLAY_NAME', supstr_plugin_info('Name'));
define('SUPSTR_AUTHOR', supstr_plugin_info('Author'));
define('SUPSTR_AUTHOR_URI', supstr_plugin_info('AuthorURI'));
define('SUPSTR_DESCRIPTION', supstr_plugin_info('Description'));

// Autoload all the requisite classes
function supstr_autoloader($class_name)
{
  // Only load SuperStripe classes here
  if(preg_match('/^Supstr.+$/', $class_name))
  {
    if(preg_match('/^SupstrBase.+$/', $class_name)) // Base classes are in lib
      $filepath = SUPSTR_LIB_PATH."/{$class_name}.php";
    else if(preg_match('/^.+Controller$/', $class_name))
      $filepath = SUPSTR_CONTROLLERS_PATH."/{$class_name}.php";
    else if(preg_match('/^.+Helper$/', $class_name))
      $filepath = SUPSTR_HELPERS_PATH."/{$class_name}.php";
    else if(preg_match('/^.+Exception$/', $class_name))
      $filepath = SUPSTR_LIB_PATH."/SupstrExceptions.php";
    else if(preg_match('/^.+Gateway$/', $class_name)) {
      foreach( SupstrGatewayFactory::paths() as $path ) {
        $filepath = $path."/{$class_name}.php";
        if( file_exists($filepath) ) {
          include_once($filepath); return;
        }
      }
      return;
    }
    else {
      $filepath = SUPSTR_MODELS_PATH."/{$class_name}.php";
    
      // Now let's try the lib dir if its not a model
      if(!file_exists($filepath))
        $filepath = SUPSTR_LIB_PATH."/{$class_name}.php";
    }
    
    if(file_exists($filepath))
      include_once($filepath);
  }
}

// if __autoload is active, put it on the spl_autoload stack
if(is_array(spl_autoload_functions()) and in_array('__autoload', spl_autoload_functions()))
  spl_autoload_register('__autoload');

// Add the autoloader
spl_autoload_register('supstr_autoloader');

// Gotta load the language before everything else
SupstrAppController::load_language();

// Load our controllers
$controllers = @glob( SUPSTR_CONTROLLERS_PATH . '/*', GLOB_NOSORT );
foreach( $controllers as $controller ) {
  $class = preg_replace( '#\.php#', '', basename($controller) );
  if( preg_match( '#Supstr.*Controller#', $class ) )
    $obj = new $class;
}

// Setup screens
SupstrAppController::setup_menus();

// Include Widgets

// Register Widgets

// Include APIs

// Template Tags

register_activation_hook( SUPSTR_PLUGIN_SLUG, create_function( '', 'require_once( SUPSTR_LIB_PATH . "/activation.php");' ) );
register_deactivation_hook( SUPSTR_PLUGIN_SLUG, create_function( '', 'require_once( SUPSTR_LIB_PATH . "/deactivation.php");' ) );
//register_uninstall_hook( SUPSTR_PLUGIN_SLUG, create_function( '', 'require_once( SUPSTR_PATH . "/uninstall.php");' ) );
