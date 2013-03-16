
<p><?php printf(__('A transaction on %s just completed successfully:'), get_option('blogname')); ?></p>
<table>
  <tr>
    <td><b><?php _e('Name:'); ?></b></td>
    <td><?php echo $json->response->charge->card->name; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Price:'); ?></b></td>
    <td><?php echo Supstr::format_currency($json->price); ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Description:'); ?></b></td>
    <td><?php echo $json->description; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Email:'); ?></b></td>
    <td><?php echo $json->email; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Invoice:'); ?></b></td>
    <td><?php echo $json->response->charge->id; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Payee:'); ?></b></td>
    <td><?php echo $json->company; ?></td>
  </tr>
  <?php
    if($json->show_name=='true' or $json->show_address=='true'):
      echo $shipping_info;
    endif;
  ?>
</table>
