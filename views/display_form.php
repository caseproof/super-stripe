<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div class="wrap">
  <h2><?php _e('Super Stripe'); ?></h2>
  <?php require(SUPSTR_PATH.'/views/errors.php'); ?>
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
