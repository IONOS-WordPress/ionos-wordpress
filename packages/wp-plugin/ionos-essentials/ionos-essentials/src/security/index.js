// tell eslint that the global variable exists when this file gets executed
/* global ionosSecurityWpData:true */
/* global jQuery:true */
jQuery(function () {
  document.querySelector('.ionos-ssl-check button.notice-dismiss')?.addEventListener('click', function () {
    jQuery.post(ionosSecurityWpData.ajaxUrl, { action: 'ionos-ssl-check-dismiss-notice' });
  });
  document.querySelector('.ionos-security-migrated button.notice-dismiss')?.addEventListener('click', function () {
    jQuery.post(ionosSecurityWpData.ajaxUrl, { action: 'ionos-security-migrated-notice' });
  });
});
