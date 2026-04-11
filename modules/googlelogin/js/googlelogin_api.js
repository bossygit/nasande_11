(function ($, Drupal) {
  Drupal.behaviors.googlelogin = {
  attach: function (context, settings) {
    jQuery(document).ready(function() {
      setTimeout(function(){
        google.accounts.id.initialize({
          client_id: settings.googlelogin.client_id,
          login_uri: settings.googlelogin.login_uri,
          ux_mode: 'redirect',
          nonce: settings.googlelogin.hash,
          context: 'signin'
        });
        google.accounts.id.prompt();

        google.accounts.id.renderButton(document.getElementById('google-signin-container'),
          {
            type: settings.googlelogin.type,
            theme: settings.googlelogin.theme,
            size: settings.googlelogin.size,
            text: settings.googlelogin.text,
            shape: settings.googlelogin.shape,
            logo_alignment: settings.googlelogin.logo_alignment,
            width: settings.googlelogin.width,
            locale: settings.googlelogin.locale
          });
      }, 500);
    });
    }
  }
})(jQuery, Drupal);
