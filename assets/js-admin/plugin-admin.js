/******/ (() => { // webpackBootstrap
/*!*********************************************!*\
  !*** ./assets/js-admin-src/plugin-admin.js ***!
  \*********************************************/
jQuery(document).ready(function ($) {
  $('.notice.wppd-admin-notice button.notice-dismiss').on('click', function () {
    $.post(WpParsiDateAdmin.ajaxUrl, {
      nonce: WpParsiDateAdmin.ajaxNonce,
      action: "wp_parsidate_dismiss_admin_notice",
      id: $(this).closest('.notice').attr('data-notice-id'),
      dismiss_time: $(this).closest('.notice').attr('data-notice-dismiss-time')
    }, function (data) {
      console.log(data);
    });
  });
});

/******/ })()
;