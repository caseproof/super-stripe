<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<form action="<?php echo home_url("index.php"); ?>" class="supstr-payment-form" method="post">
  <input type="hidden" name="plugin" value="supstr" />
  <input type="hidden" name="action" value="process" />
  <input type="hidden" name="args" value="<?php echo $form_count; ?>" />
  <input type="hidden" name="pid" value="<?php echo $post->ID; ?>" />
  <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('Super Stripe Payment Form'); ?>" />
  <div class="super-stripe-form">
    <label for="supstr_email"><?php _e('Email Address:'); ?>
    <input type="text" name="supstr_email" size="30" value="<?php echo isset($_REQUEST['supstr_email']) ? $_REQUEST['supstr_email'] : ''; ?>" class="email required" /></label><br/>
    <input type="submit" name="checkout" class="supstr-payment-form-submit" value="<?php echo $button; ?>" />
    <span class="supstr_loading_gif" style="display:none;vertical-align:middle;"><img src="<?php echo site_url('/wp-admin/images/wpspin_light.gif'); ?>" /></span>
  </div>
</form>
