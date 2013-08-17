<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

//Loop for each record
if(!empty($records))
{
  foreach($records as $rec)
  {
    //Open the line
    ?>
    <tr id="record_<?php echo $rec->ID; ?>">
    <?php
    $show_name = get_post_meta($rec->ID, '_supstr_txn_show_name', true);
    $show_address = get_post_meta($rec->ID, '_supstr_txn_show_address', true);

    foreach($columns as $column_name => $column_display_name)
    {
      //Style attributes for each col
      $class = "class=\"{$column_name} column-{$column_name}\"";
      $style = "";
      if(in_array($column_name, $hidden))
        $style = ' style="display:none;"';
      $attributes = $class.$style;

      //Display the cell
      switch($column_name)
      {
        case 'col_date':
          ?>
          <td <?php echo $attributes; ?>><?php echo $rec->date; ?></td>
          <?php
          break;
        case 'col_price':
          ?>
          <td <?php echo $attributes; ?>><?php echo Supstr::format_currency($rec->price); ?></td>
          <?php
          break;
        case 'col_txn_num':
          ?>
          <td <?php echo $attributes; ?>><?php echo $rec->txn_num; ?> <?php if($show_name or $show_address) { ?>(<a href="#" class="supstr-toggle-extended" data-id="<?php echo $rec->ID; ?>"><?php _e('Shipping Info'); ?></a>)<?php } ?></td>
          <?php
          break;
        case 'col_description':
          ?>
          <td <?php echo $attributes; ?>><?php echo $rec->description; ?></td>
          <?php
          break;
        case 'col_email':
          ?>
          <td <?php echo $attributes; ?>><?php echo $rec->email; ?></td>
          <?php
          break;
        case 'col_buyer_name':
          ?>
          <td <?php echo $attributes; ?>><?php echo $rec->buyer_name; ?></td>
          <?php
          break;
      }
    }
    
    ?>
    </tr>
    <?php

    if($show_name or $show_address) {
      ?>
      <tr id="extended_record_<?php echo $rec->ID; ?>" class="supstr-extended">
        <td colspan="6">
          <div class="supstr-extended-info-title"><?php _e('Shipping Info'); ?></div>
          <div class="supstr-extended-info-table">
          <?php
          if($show_name) {
            $first_name = get_post_meta($rec->ID, '_supstr_txn_first_name', true);
            $last_name  = get_post_meta($rec->ID, '_supstr_txn_last_name', true);
            ?>
            <div class="supstr-extended-info-first-name">
              <div class="supstr-extended-info-label"><?php _e('First Name:'); ?></div>
              <div class="supstr-extended-info-field"><?php echo $first_name; ?></div>
            </div>
            <div class="supstr-extended-info-last-name">
              <div class="supstr-extended-info-label"><?php _e('Last Name:'); ?></div>
              <div class="supstr-extended-info-field"><?php echo $last_name; ?></div>
            </div>
            <?php
          }

          if($show_address) {
            $address = get_post_meta($rec->ID, '_supstr_txn_address', true);
            $city    = get_post_meta($rec->ID, '_supstr_txn_city', true);
            $state   = get_post_meta($rec->ID, '_supstr_txn_state', true);
            $zip     = get_post_meta($rec->ID, '_supstr_txn_zip', true);
            $country = get_post_meta($rec->ID, '_supstr_txn_country', true);
            ?>
            <div class="supstr-extended-info-address">
              <div class="supstr-extended-info-label"><?php _e('Address:'); ?></div>
              <div class="supstr-extended-info-field"><?php echo $address; ?></div>
            </div>
            <div class="supstr-extended-info-city">
              <div class="supstr-extended-info-label"><?php _e('City:'); ?></div>
              <div class="supstr-extended-info-field"><?php echo $city; ?></div>
            </div>
            <div class="supstr-extended-info-state">
              <div class="supstr-extended-info-label"><?php _e('State:'); ?></div>
              <div class="supstr-extended-info-field"><?php echo $state; ?></div>
            </div>
            <div class="supstr-extended-info-zip">
              <div class="supstr-extended-info-label"><?php _e('Zip:'); ?></div>
              <div class="supstr-extended-info-field"><?php echo $zip; ?></div>
            </div>
            <div class="supstr-extended-info-country">
              <div class="supstr-extended-info-label"><?php _e('Country:'); ?></b></div>
              <div class="supstr-extended-info-field"><?php echo $country; ?></div>
            </div>
            <?php
          }
          ?>
          </div>
        </td>
      </tr>
    <?php
    }
  } //End foreach
} //End if
