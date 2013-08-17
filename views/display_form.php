<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<script type="text/javascript">
jQuery(document).ready(function() {
  jQuery('.supstr-show-hide-aws-config a').text(jQuery('.supstr-show-hide-aws-config a').attr('data-show'));
  jQuery('.supstr-show-hide-aws-config a').click( function() {
    if( jQuery(this).attr('data-action') == 'show' ) {
      jQuery('.supstr-aws-config').slideDown();
      jQuery(this).text(jQuery(this).attr('data-hide'));
      jQuery(this).attr('data-action','hide');
    }
    else {
      jQuery('.supstr-aws-config').slideUp();
      jQuery(this).text(jQuery(this).attr('data-show'));
      jQuery(this).attr('data-action','show');
    }
    return false;
  });
});
</script>
<div class="wrap">
  <h2><?php _e('<em>Buy Now for Stripe</em> Options'); ?></h2>
  <br/>
  <?php require(SUPSTR_PATH.'/views/errors.php'); ?>
  <form method="post" action="">
    <?php wp_nonce_field('super-stripe','_supstr_nonce'); ?>
    <label for="supstr_license_key"><b><?php _e('API Key:'); ?></b> </label>
    <input type="text" name="supstr_license_key" class="regular-text" value="<?php echo esc_attr($license_key); ?>" />
    <br/><br/>
    <div class="supstr-show-hide-aws-config"><a href="#" class="button" data-action="show" data-hide="<?php _e('Hide AWS Account Credentials'); ?>" data-show="<?php _e('Edit AWS Account Credentials'); ?>"></a></div>
    <div class="supstr-aws-config" style="display:none;padding-left:15px;">
      <h3><?php _e('AWS Credentials:'); ?></h3>
      <table>
        <tr>
          <td><label for="supstr_aws_access_key"><?php _e('AWS Access Key:'); ?> </label></td>
          <td><input type="text" name="supstr_aws_access_key" class="regular-text" value="<?php echo esc_attr($access_key); ?>" /></td>
        </tr>
        <tr>
          <td><label for="supstr_aws_secret_key"><?php _e('AWS Secret Key:'); ?> </label></td>
          <td><input type="text" name="supstr_aws_secret_key" class="regular-text" value="<?php echo esc_attr($secret_key); ?>" /></td>
        </tr>
      </table>
    </div>
    <?php submit_button(); ?>
  </form>
  <div class="supstr-connection">
    <?php if( $license_key ): ?>
      <?php if( $connected ): ?>
        <h3><?php _e('This license key is connected to Stripe and active. Happy credit card processing!'); ?></h3>
      <?php else: ?>
        <h3><?php printf(__('This is not connected to Stripe. Please %1$slogin%2$s or %3$sregister for a free account%2$s on buynowforstripe.com to connect this key to your Stripe credit card processing account.'), '<a href="http://buynowforstripe.com/login">', '</a>', '<a href="http://buynowforstripe.com/register/super-stripe">'); ?></h3>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <br/>
  <iframe width="640" height="360" src="http://www.youtube.com/embed/UoVTEHWZiGg" frameborder="0" allowfullscreen></iframe>
</div>

