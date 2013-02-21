<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div class="wrap">
  <h2><?php _e('Super Stripe'); ?></h2>
  <br/>
  <?php require(SUPSTR_PATH.'/views/errors.php'); ?>
  <form method="post" action="">
    <?php wp_nonce_field('super-stripe','_supstr_nonce'); ?>
    <label for="supstr_license_key"><b><?php _e('License Key:'); ?></b> </label>
    <input type="text" name="supstr_license_key" class="regular-text" value="<?php echo esc_attr(get_option('supstr_license_key')); ?>" />
    <?php submit_button(); ?>
  </form>
</div>
