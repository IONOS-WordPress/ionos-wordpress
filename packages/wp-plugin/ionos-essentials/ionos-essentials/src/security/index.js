import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

domReady(() => {
  document.addEventListener('click', async function (event) {
    const buttonSSLCheck = event.target.closest('.ionos-ssl-check button.notice-dismiss');
    const buttonSecurityMigrated = event.target.closest('.ionos-security-migrated button.notice-dismiss');
    if (!buttonSSLCheck && !buttonSecurityMigrated) return;

    const action = buttonSecurityMigrated ? 'ionos-security-migrated-notice' : 'ionos-ssl-check-dismiss-notice';

    try {
      await apiFetch({
        url: window.ajaxurl || '/wp-admin/admin-ajax.php',
        method: 'POST',
        body: new URLSearchParams({
          action: action,
        }),
      });
    } catch (error) {
      console.error('Failed to dismiss notice:', error);
    }
  });
});
