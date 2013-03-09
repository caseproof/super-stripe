<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<p><?php _e('Dear {$first_name},'); ?></p>
<p><?php printf(__('Thank you for your purchase on %s. Keep this email for your records:'), get_option('blogname')); ?></p>
<br/>
{$txn_receipt}
<br/>
<p><?php _e('Cheers,'); ?><br/><br/>
   <?php printf(__('The %s Team'), get_option('blogname')); ?></p>
