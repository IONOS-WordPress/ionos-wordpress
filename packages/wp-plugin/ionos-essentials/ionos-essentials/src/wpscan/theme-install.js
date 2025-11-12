import { __ } from '@wordpress/i18n';

// tell eslint that the global variable exists when this file gets executed
/* global ionosWPScanThemes:true */
/* global jQuery:true */
document.addEventListener('DOMContentLoaded', function () {
  function addInstallListener() {
    document.querySelectorAll('.theme-actions .theme-install').forEach(function (button) {
      button.addEventListener('click', function (event) {
        if (!event.target.dataset.safe) {
          event.preventDefault();
          event.stopPropagation();
        }

        if (event.target.dataset.disabled) {
          return;
        }
        event.target.dataset.disabled = 'true';

        const themeCard = event.target.closest('.theme');

        const html = `<p>${__('Checking for vulnerabilities...', 'ionos-essentials')}</p>`;

        const notice = document.createElement('div');
        notice.innerHTML = html;
        notice.classList.add(
          'update-message',
          'notice',
          'inline',
          'notice-warning',
          'notice-alt',
          'ionos-theme-issues'
        );
        themeCard?.appendChild(notice);

        jQuery
          .post(ionosWPScanThemes.ajaxUrl, {
            action: 'ionos-wpscan-instant-check',
            _ajax_nonce: ionosWPScanThemes.nonce,
            slug: event.target.dataset.slug,
            type: 'theme',
          })
          .always(function (response) {
            switch (response.data) {
              case 'warnings_found':
                notice.innerHTML = `<p>${__('Warnings found. Installation is not recommended.', 'ionos-essentials')}</p>`;
                notice.classList.add('notice-info');
                event.target.dataset.safe = 'true';
                event.target.dataset.disabled = 'false';
                break;
              case 'criticals_found':
                notice.innerHTML = `<p>${__('Critical vulnerabilities found! Installation is not possible.', 'ionos-essentials')}</p>`;
                notice.classList.remove('notice-warning');
                notice.classList.add('notice-error');
                break;
              default:
                notice.innerHTML = `<p>${__('No vulnerabilities found. You can safely install this theme.', 'ionos-essentials')}</p>`;
                notice.classList.remove('notice-warning');
                notice.classList.add('notice-success');
                event.target.dataset.safe = 'true';
                event.target.dataset.disabled = 'false';
                event.target.click();
                break;
            }
          });
      });
    });
  }
  addInstallListener();

  const observer = new MutationObserver(() => {
    addInstallListener();
  });

  observer.observe(document.body, { childList: true, subtree: true });
});
