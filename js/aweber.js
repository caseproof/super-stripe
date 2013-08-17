(function ($) {
  $(document).ready(function() {
    var supstr_get_post_args = function(form) {
      var listname = $(form).find('.supstr_aweber_optin').attr('data-list');
      var args = { listname: listname,
                   meta_adtracking: 'buy-now-for-stripe',
                   meta_message: 1,
                   meta_forward_vars: 1,
                   email: $(form).find('.supstr_email .supstr_field input').val()
                 };
      var name = '';
      if( $('.supstr_first_name .supstr_field input').val() != undefined &&
          $('.supstr_first_name .supstr_field input').val() != null &&
          $('.supstr_first_name .supstr_field input').val() != '' ) {
        name = $('.supstr_first_name .supstr_field input').val();
        if( $('.supstr_last_name .supstr_field input').val() != undefined &&
            $('.supstr_last_name .supstr_field input').val() != null &&
            $('.supstr_last_name .supstr_field input').val() != '' ) {
          name = name + ' ' + $('.supstr_last_name .supstr_field input').val();
        }
        args['name'] = name;
      }
      args['redirect'] = 'http://www.aweber.com/thankyou-coi.htm?m=text';
      return args;
    }

    var supstr_submit_form = function( form ) {
      // We're bypassing the aweber api due to its complexity and
      // opting for a straight js post from the client side now
      if( form.find('.supstr_aweber_optin').is(':checked') && form.find('.supstr_email .supstr_field input').val()!='' ) {
        
        args = supstr_get_post_args(form);
        $.post( "http://www.aweber.com/scripts/addlead.pl", args ).complete( function() { 
          form.submit();
        });
      }
      else {
        form.submit();
      }
    }

    $('.supstr-payment-form input').keypress( function(e) {
      if(e.which==13) {
        e.preventDefault();
        // Yup, that's right ... 5 levels of nesting bro
        supstr_submit_form( $(this).parent().parent().parent().parent().parent() ); 
      }
    });

    $('.supstr-payment-form input[type=submit],.supstr-payment-form input[type=image]').click( function(e) {
      e.preventDefault();
      // Yup, that's right ... 3 levels of nesting bro
      supstr_submit_form( $(this).parent().parent().parent() ); 
    });
  });
})(jQuery);
