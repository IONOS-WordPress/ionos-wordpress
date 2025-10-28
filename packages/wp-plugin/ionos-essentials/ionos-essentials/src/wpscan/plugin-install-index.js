// tell eslint that the global variable exists when this file gets executed
/* global ionosWPScanPlugins:true */
/* global jQuery:true */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.install-now').forEach(function (button) {
    button.addEventListener('click', function (event) {
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
      message.innerHTML = `<p>${ionosWPScanPlugins.i18n.checking}</p>`;
      pluginCard?.insertBefore(message, pluginCard.firstChild);

      jQuery
        .post(ionosWPScanPlugins.ajaxUrl, {
          action: 'ionos-wpscan-instant-check',
          _ajax_nonce: ionosWPScanPlugins.nonce,
          slug: event.target.dataset.slug,
          type: 'plugin',
        })
        .done(function (response) {
          switch (response.data) {
            case 'warnings_found':
              message.innerHTML = `<p>${ionosWPScanPlugins.i18n.warnings_found}</p>`;
              message.classList.add('notice-info');
              event.target.dataset.safe = 'true';
              event.target.dataset.disabled = 'false';
              break;
            case 'criticals_found':
              message.innerHTML = `<p>${ionosWPScanPlugins.i18n.critical_found}</p>`;
              message.classList.remove('notice-warning');
              message.classList.add('notice-error');
              break;
            default:
              message.innerHTML = `<p>${ionosWPScanPlugins.i18n.nothing_found}</p>`;
              message.classList.remove('notice-warning');
              message.classList.add('notice-success');
              event.target.dataset.safe = 'true';
              event.target.click();
              break;
          }
        });
    });
  });
});
