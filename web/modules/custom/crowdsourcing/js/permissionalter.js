(function ($, window, Drupal, drupalSettings) {
  "use strict";
  // Get to given element
    $("[id^='already_reported_']").each(function() {
        var cid = $(this).attr('data-id');
        $('#comment-'+cid).hide();
    });
})(jQuery, this, Drupal, drupalSettings);