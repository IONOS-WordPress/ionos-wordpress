jQuery(function ($) {
  document.querySelector('.ionos-ssl-check button.notice-dismiss')?.addEventListener('click', function (event) {
    jQuery.post(ionosSecurityWpData.ajaxUrl, { action: 'ionos-ssl-check-dismiss-notice' });
  });
  document.querySelector('.ionos-security-migrated button.notice-dismiss')?.addEventListener('click', function (event) {
    jQuery.post(ionosSecurityWpData.ajaxUrl, { action: 'ionos-security-migrated-notice' });
  });
});
