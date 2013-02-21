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
          <td <?php echo $attributes; ?>><?php echo $rec->txn_num; ?></td>
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
  } //End foreach
} //End if
