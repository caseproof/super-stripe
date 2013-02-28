(function() {
  tinymce.create('tinymce.plugins.superstripe', {
    init : function(ed, url) {
http://cpwafp.caseproof.com/wp-content/plugins/super-stripe/js
      // Super Stripe Form Shortcode
      ed.addCommand('superstripe_form', function() {
        ed.windowManager.open({
          file : ajaxurl + '?action=supstr_shortcode_form', // file that contains HTML for our modal window
          width : 500 + parseInt(ed.getLang('button.delta_width', 0)), // size of our window
          height : 575 + parseInt(ed.getLang('button.delta_height', 0)), // size of our window
          inline : 1
        }, {
          plugin_url : url
        });
      });
      ed.addButton('superstripe_form', {title : 'Insert Super Stripe Form', cmd : 'superstripe_form', image: url + '/../images/tinymce_form_popup.png'});
    },

    getInfo : function() {
      return {
        longname : 'SuperStripe',
        author : 'Caseproof, LLC',
        authorurl : 'http://www.caseproof.com',
        infourl : 'http://www.caseproof.com',
        version : tinymce.majorVersion + "." + tinymce.minorVersion
      };
    }
  });

  // Register plugin
  // first parameter is the button ID and must match ID elsewhere
  // second parameter must match the first parameter of the tinymce.create() function above
  tinymce.PluginManager.add('SuperStripe', tinymce.plugins.superstripe);

})();

