<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<form action="<?php echo home_url("index.php"); ?>" class="supstr-shortcode-form" method="post">
  <input type="hidden" name="plugin" value="supstr" />
  <input type="hidden" name="action" value="process" />
  <input type="hidden" name="args" value="<?php echo base64_encode(json_encode($args)); ?>" />
  <div class="super-stripe-form">
    <label for="supstr_email"><?php _e('Email Address:'); ?>
    <input type="text" name="supstr_email" value="<?php echo isset($_REQUEST['supstr_email']) ? $_REQUEST['supstr_email'] : ''; ?>" class="email required" /></label><br/>
    <input type="submit" name="checkout" value="<?php echo $button; ?>" />
    <span class="supstr_loading_gif" style="display:none;vertical-align:middle;"><img src="<?php echo site_url('/wp-admin/images/wpspin_light.gif'); ?>" /></span>
  </div>
</form>
