<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div id="supstr-meta-box-form">
  <div class="supstr_form_row">
    <label for="supstr_form_terms"><?php _e('Terms', 'super-stripe'); ?></label>
    <input type="text" name="supstr_form_terms" id="supstr_form_terms" value="<?php echo (isset($post_meta['supstr_form_terms']))?stripslashes($post_meta['supstr_form_terms']):''; ?>" />
  </div>
  
  
</div>

<!--
currency => 'USD'
terms => text (done)
description => textarea
return_url => text
cancel_url => text
sale_notice_emails => text
customer_email => wp_editor
-->
