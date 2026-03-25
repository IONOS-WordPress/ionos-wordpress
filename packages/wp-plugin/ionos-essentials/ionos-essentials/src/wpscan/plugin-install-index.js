import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

// tell eslint that the global variable exists when this file gets executed
/* global ionosWPScanPlugins:true */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.install-now').forEach(function (button) {
    button.addEventListener('click', async function (event) {
      if (!event.target.dataset.safe) {
        event.preventDefault();
        event.stopPropagation();
      }

      if (event.target.dataset.disabled) {
        return;
      }
      event.target.dataset.disabled = 'true';

      const pluginCard = event.target.closest('.plugin-card');

      const message = document.createElement('div');
      message.classList.add('notice', 'notice-alt', 'notice-warning', 'inline');
      message.innerHTML = `<p>${__('Checking for vulnerabilities...', 'ionos-essentials')}</p>`;
      pluginCard?.insertBefore(message, pluginCard.firstChild);

      try {
        await apiFetch({
          url: window.ajaxurl || '/wp-admin/admin-ajax.php',
          method: 'POST',
          body: new URLSearchParams({
            action: 'ionos-wpscan-instant-check',
            _ajax_nonce: ionosWPScanPlugins.nonce,
            slug: event.target.dataset.slug,
            type: 'plugin',
          }),
        }).then((response) => {
          switch (response.data) {
            case 'warnings_found':
              message.innerHTML = `<p>${__('Warnings found. Installation is not recommended.', 'ionos-essentials')}</p>`;
              message.classList.add('notice-info');
              event.target.dataset.safe = 'true';
              event.target.dataset.disabled = 'false';
              break;
            case 'criticals_found':
              message.innerHTML = `<p>${__('Critical vulnerabilities found! Installation is not possible.', 'ionos-essentials')}</p>`;
              message.classList.remove('notice-warning');
              message.classList.add('notice-error');
              break;
            default:
              message.innerHTML = `<p>${__('No vulnerabilities found. You can safely install this plugin.', 'ionos-essentials')}</p>`;
              message.classList.remove('notice-warning');
              message.classList.add('notice-success');
              event.target.dataset.safe = 'true';
              event.target.click();
              break;
          }
        });
      } catch (error) {
        console.error('Failed to load vulnerabilities:', error);
      }
    });
  });
});
