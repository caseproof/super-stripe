<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<form action="<?php echo home_url("index.php"); ?>" method="post">
  <input type="hidden" name="plugin" value="supstr" />
  <input type="hidden" name="action" value="process" />
  <input type="hidden" name="args" value="<?php echo base64_encode(json_encode($args)); ?>" />
  <div class="super-stripe-form">
    <label for="supstr_email"><?php _e('Email Address:'); ?>
    <input type="text" name="supstr_email" value="<?php echo isset($_REQUEST['supstr_email']) ? $_REQUEST['supstr_email'] : ''; ?>" /></label><br/>
    <input type="submit" name="checkout" value="<?php echo $content; ?>" />
  </div>
</form>
