<?php
// this file contains the contents of the popup window
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php _e('Insert Super Stripe Payment Form Shortcode'); ?></title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo home_url( '/wp-includes/js/tinymce/tiny_mce_popup.js' ); ?>"></script>
<style type="text/css" src="<?php echo '/wp-includes/js/tinymce/themes/advanced/skins/wp_theme/dialog.css'; ?>"></style>
<style type="text/css">
  #supstr-form-dialog { }
  #supstr-form-dialog div{ padding: 5px 0; height: 20px; }
  #supstr-form-dialog #errors { color: red; font-weight: bold; }
  #supstr-form-dialog #docs {float: right;}
  #supstr-form-dialog label { display: block; float: left; margin: 0 8px 0 0; width: 180px; }
  #supstr-form-dialog input { display: block; float: right; width: 280px; padding: 3px 5px; }
  #supstr-form-dialog #insert { display: block; line-height: 24px; text-align: center; margin: 10px 0 0 0; width: 100%; float: right; text-decoration: none; }
  #supstr-form-dialog textarea { display: block; float: right; width: 280px; height: 200px; padding: 3px 5px; }
</style>

<script type="text/javascript">
  var ButtonDialog = {
    local_ed : 'ed',
    init : function(ed) {
      ButtonDialog.local_ed = ed;
      tinyMCEPopup.resizeToInnerSize();
    },
    insert : function insertButton(ed) {

      // Try and remove existing style / blockquote
      tinyMCEPopup.execCommand('mceRemoveNode', false, null);

      var terms = jQuery('#supstr-form-dialog input#terms').val();
      var description = jQuery('#supstr-form-dialog input#description').val();
      var price = jQuery('#supstr-form-dialog input#price').val();
      var return_url = jQuery('#supstr-form-dialog input#return_url').val();
      var cancel_url = jQuery('#supstr-form-dialog input#cancel_url').val();
      var livemode = jQuery('#supstr-form-dialog select#livemode').val();
      var sale_notice_emails = jQuery('#supstr-form-dialog input#sale_notice_emails').val();
      var button = jQuery('#supstr-form-dialog input#button').val();
      var currency = jQuery('#supstr-form-dialog input#currency').val();
      var email = jQuery('#supstr-form-dialog textarea#email').val();

      if( terms == '' ) {
        jQuery("#errors").html('Terms must not be blank');
      }
      else if( description == '' ) {
        jQuery("#errors").html('Description must not be blank');
      }
      else if( price == '' ) {
        jQuery("#errors").html('Price must not be blank');
      }
      else if( !price.match( /^\d+\.\d{2}/ ) ) {
        jQuery("#errors").html('Price must match the format ###.##');
      }
      else if( return_url == '' ) {
        jQuery("#errors").html('Return URL must not be blank');
      }
      else if( !return_url.match( /https?:\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?/ ) ) {
        jQuery("#errors").html('Return URL must be valid, full URL');
      }
      else if( cancel_url == '' ) {
        jQuery("#errors").html('Cancel URL must not be blank');
      }
      else if( !cancel_url.match( /https?:\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?/ ) ) {
        jQuery("#errors").html('Cancel URL must be valid, full URL');
      }
      else {
        // setup the output of our shortcode
        var output = '[super-stripe-form ';
          output += 'terms="' + terms + '" ';
          output += 'description="' + description + '" ';
          output += 'price="' + price + '" ';
          output += 'return_url="' + return_url + '" ';
          output += 'cancel_url="' + cancel_url + '" ';
          output += 'livemode="' + livemode + '" ';
          output += 'sale_notice_emails="' + sale_notice_emails + '" ';
          output += 'button="' + button + '" ';
          output += 'currency="' + currency + '" ';
        output += ']';

        if( email != '' ) {
          output += email + '[/super-stripe-form]';
        }

        tinyMCEPopup.execCommand('mceReplaceContent', false, output);

        // Return
        tinyMCEPopup.close();
      }
    }
  };
  tinyMCEPopup.onInit.add(ButtonDialog.init, ButtonDialog);
</script>

</head>
<body>
  <div id="supstr-form-dialog">
    <div id="docs">
      <a href="http://superstripeapp.com/docs/#shortcode"><?php _e('User Manual', 'super-stripe'); ?></a>
    </div>
    <div id="errors"></div>
    <form action="/" method="get" accept-charset="utf-8">
      <div>
        <label for="terms"><?php _e('Terms*'); ?></label>
        <input type="text" name="terms" value="" id="terms" />
      </div>
      <div>
        <label for="description"><?php _e('Description*'); ?></label>
        <input type="text" name="description" value="" id="description" />
      </div>
      <div>
        <label for="price"><?php _e('Price* ($)', 'super-stripe'); ?></label>
        <input type="text" name="price" value="0.00" id="price" />
      </div>
      <div>
        <label for="return_url"><?php _e('Return URL*', 'super-stripe'); ?></label>
        <input type="text" name="return_url" value="<?php echo home_url(); ?>" id="return_url" />
      </div>
      <div>
        <label for="cancel_url"><?php _e('Cancel URL*', 'super-stripe'); ?></label>
        <input type="text" name="cancel_url" value="<?php echo home_url(); ?>" id="cancel_url" />
      </div>
      <div>
        <label for="livemode"><?php _e('Live / Production Mode', 'super-stripe'); ?></label>
        <select name='livemode' id='livemode'>
          <option value="false"><?php _e('False', 'super-stripe'); ?></option>
          <option value="true"><?php _e('True', 'super-stripe'); ?></option>
        </select>
      </div>
      <div>
        <label for="sale_notice_emails"><?php _e('Admin sale notice emails', 'super-stripe'); ?></label>
        <input type="text" name="sale_notice_emails" value="<?php echo get_option('admin_email'); ?>" id="sale_notice_emails" />
      </div>
      <div>
        <label for="button"><?php _e('Button text', 'super-stripe'); ?></label>
        <input type="text" name="button" value="<?php _e('Buy Now','super-stripe'); ?>" id="button" />
      </div>
      <div>
        <label for="currency"><?php _e('Currency code', 'super-stripe'); ?></label>
        <input type="text" name="currency" value="USD" id="currency" />
      </div>
      <div>
        <label for="currency"><?php _e('Custom Customer Email', 'super-stripe'); ?></label>
        <textarea name="email" id="email"></textarea>
      </div>
      <div>
        <a href="javascript:ButtonDialog.insert(ButtonDialog.local_ed)" id="insert"><?php _e('Insert', 'super-stripe'); ?></a>
      </div>
    </form>
  </div>
</body>
</html>
