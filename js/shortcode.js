(function($) {
  $(document).ready(function() {
    //Fire off the validator
    $('.supstr-shortcode-form').validate({
      submitHandler: function(form, validator) {
        //Show loading image if input is valid and submit is triggered
        $('.supstr_loading_gif').show(400,function() {
          form.submit();
        });
      }
    });
  });
})(jQuery);
