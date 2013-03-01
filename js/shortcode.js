(function($) {
  $(document).ready(function() {
    //Fire off the validator for each payment form on the page
    $('.supstr-payment-form').each(function() {
      $(this).validate({
        submitHandler: function(form, validator) {
          //Show loading image if input is valid and submit is triggered
          $(form).find('.supstr_loading_gif').show(400,function() {
            form.submit();
          });
        }
      });
    });
  });
})(jQuery);
