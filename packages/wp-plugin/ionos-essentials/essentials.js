window.addEventListener('load', function () {
  document.querySelector('.ionos-ssl-check button.notice-dismiss')?.addEventListener('click', function (event) {
    jQuery.post(wpData.ajaxUrl, {'action': 'ionos-ssl-check-dismiss-notice'});
  });
});
