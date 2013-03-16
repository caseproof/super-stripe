<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<?php if($json->show_name=='true'): ?>
  <tr>
    <td><b><?php _e('First Name:'); ?></b></td>
    <td><?php echo $json->first_name; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Last Name:'); ?></b></td>
    <td><?php echo $json->last_name; ?></td>
  </tr>
<?php endif; ?>
<?php if($json->show_address=='true'): ?>
  <tr>
    <td><b><?php _e('Address:'); ?></b></td>
    <td><?php echo $json->address; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('City:'); ?></b></td>
    <td><?php echo $json->city; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('State/Province:'); ?></b></td>
    <td><?php echo $json->state; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Postal Code:'); ?></b></td>
    <td><?php echo $json->zip; ?></td>
  </tr>
  <tr>
    <td><b><?php _e('Country:'); ?></b></td>
    <td><?php echo $json->country; ?></td>
  </tr>
<?php endif; ?>
