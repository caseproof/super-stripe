<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<div class="error"><ul><li><?php printf(__('You need to %sSetup Buy Now for Stripe%s before you can start charging credit cards.'), '<a href="'.admin_url('admin.php?page=buy-now-options').'">', '</a>'); ?></li></ul></div>
