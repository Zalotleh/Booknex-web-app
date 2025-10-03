(function ($) {
  "use strict";

  $(document).ready(function () {

    booknetic.boostore.onInstall = function (el, res) {
      el.removeClass("btn-success btn-install");
      el.addClass("btn-outline-danger btn-uninstall");
      el.text("UNINSTALL");
    };

    booknetic.boostore.onUninstall = function (el, res) {
      el.removeClass("btn-outline-danger btn-uninstall");
      el.addClass("btn-success btn-install");
      el.text("INSTALL");
    };

    $( document ).on( 'click', '.btn-addon-setup', function ()
    {
      const addon = $( this ).data( 'addon' ).split( '-' )[ 1 ];

      booknetic.ajax( 'tour_guide_setup_done', { addon }, function ()
      {
        location.href = `?page=${ BACKEND_SLUG }&module=${ addon }&tour_guide`;
      });
    });
  });
})(jQuery);
