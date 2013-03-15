<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<form action="<?php echo home_url("index.php"); ?>" class="supstr-payment-form" method="post">
  <input type="hidden" name="plugin" value="supstr" />
  <input type="hidden" name="action" value="process" />
  <input type="hidden" name="args" value="<?php echo $form_count; ?>" />
  <input type="hidden" name="pid" value="<?php echo $post->ID; ?>" />
  <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('Super Stripe Payment Form'); ?>" />
  <div class="super-stripe-form">
    <?php if( isset($show_name) and $show_name ): ?>
      <div class="supstr_name">
        <div class="supstr_first_name">
          <div class="supstr_label"><label for="supstr_first_name"><?php _e('First Name:'); ?></label></div>
          <div class="supstr_field"><input type="text" size="30" name="supstr_first_name" /></div>
        </div>
        <div class="supstr_last_name">
          <div class="supstr_label"><label for="supstr_last_name"><?php _e('Last Name:'); ?></label></div>
          <div class="supstr_field"><input type="text" size="30" name="supstr_last_name" /></div>
        </div>
      </div>
    <?php endif; ?>
    <?php if( isset($show_address) and $show_address ): ?>
      <div class="supstr_address">
        <div class="supstr_address">
          <div class="supstr_label"><label for="supstr_address"><?php _e('Address:*'); ?></label></div>
          <div class="supstr_field"><input type="text" size="30" name="supstr_address" class="required" /></div>
        </div>
        <div class="supstr_city">
          <div class="supstr_label"><label for="supstr_city"><?php _e('City:*'); ?></label></div>
          <div class="supstr_field"><input type="text" size="30" name="supstr_city" class="required" /></div>
        </div>
        <div class="supstr_state">
          <div class="supstr_label"><label for="supstr_state"><?php _e('State/Province:*'); ?></label></div>
          <div class="supstr_field"><input type="text" size="30" name="supstr_state" class="required" /></div>
        </div>
        <div class="supstr_zip">
          <div class="supstr_label"><label for="supstr_zip"><?php _e('Postal Code:*'); ?></label></div>
          <div class="supstr_field"><input type="text" size="30" name="supstr_zip" class="required" /></div>
        </div>
        <div class="supstr_country">
          <div class="supstr_label"><label for="supstr_country"><?php _e('Country:*'); ?></label></div>
          <div class="supstr_field"><input type="text" size="30" name="supstr_country" class="required" /></div>
        </div>
      </div>
    <?php endif; ?>
    <div class="supstr_required">
      <div class="supstr_email">
        <div class="supstr_label"><label for="supstr_email"><?php _e('Email Address:*'); ?></div>
        <div class="supstr_field"><input type="text" size="30" name="supstr_email" class="email required" /></label></div>
      </div>
    </div>
    <?php if( isset($aweber) and $aweber ): ?>
      <div class="supstr_aweber">
        <label for="supstr_aweber"><input type="checkbox" class="supstr_aweber_optin" name="supstr_aweber" checked="checked" data-list="<?php echo $aweber_list; ?>" /> <?php echo $aweber_message; ?></label>
      </div>
    <?php endif; ?>
    <?php if( isset($mailchimp) and $mailchimp ): ?>
      <div class="supstr_mailchimp">
        <label for="supstr_mailchimp"><input type="checkbox" name="supstr_mailchimp" checked="checked" /> <?php echo $mailchimp_message; ?></label>
      </div>
    <?php endif; ?>
    <div class="supstr_checkout">
      <input type="submit" name="checkout" class="supstr-payment-form-submit" value="<?php echo $button; ?>" />
      <span class="supstr_loading_gif" style="display:none;vertical-align:middle;"><img src="<?php echo site_url('/wp-admin/images/wpspin_light.gif'); ?>" /></span>
    </div>
  </div>
</form>
