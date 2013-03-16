<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<table>
  <tr>
    <td><b><?php _e('Name:'); ?></b></td>
    <td>{$txn_buyer_name}</td>
  </tr>
  <tr>
    <td><b><?php _e('Price:'); ?></b></td>
    <td>{$txn_price}</td>
  </tr>
  <tr>
    <td><b><?php _e('Description:'); ?></b></td>
    <td>{$txn_desc}</td>
  </tr>
  <tr>
    <td><b><?php _e('Payee:'); ?></b></td>
    <td>{$txn_company}</td>
  </tr>
  <tr>
    <td><b><?php _e('Invoice:'); ?></b></td>
    <td>{$txn_num}</td>
  </tr>
  <tr>
    <td><b><?php _e('Email:'); ?></b></td>
    <td>{$txn_email}</td>
  </tr>
  {$txn_shipping_info} 
</table>
