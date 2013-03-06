<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div class="wrap">
  <h2><?php _e('Super Stripe Options'); ?></h2>
  <br/>
  <?php require(SUPSTR_PATH.'/views/errors.php'); ?>
  <form method="post" action="">
    <?php wp_nonce_field('super-stripe','_supstr_nonce'); ?>
    <label for="supstr_license_key"><b><?php _e('API Key:'); ?></b> </label>
    <input type="text" name="supstr_license_key" class="regular-text" value="<?php echo esc_attr($license_key); ?>" />
    <br/>
    <?php submit_button(); ?>
  </form>
  <div class="supstr-connection">
    <?php if( $license_key ): ?>
      <?php if( $connected ): ?>
        <h3><?php _e('This license key is connected to Stripe and active. Happy credit card processing!'); ?></h3>
      <?php else: ?>
        <h3><?php printf(__('This is not connected to Stripe. Please %1$slogin%2$s or %3$sregister for a free account%2$s on superstripeapp.com to connect this key to your Stripe credit card processing account.'), '<a href="http://superstripeapp.com/login">', '</a>', '<a href="http://superstripeapp.com/register/super-stripe">'); ?></h3>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <br/>
  <iframe width="640" height="360" src="http://www.youtube.com/embed/UoVTEHWZiGg" frameborder="0" allowfullscreen></iframe>
</div>
