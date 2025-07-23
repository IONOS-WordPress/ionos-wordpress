document.addEventListener('DOMContentLoaded', function () {

  function addInstallListener(){
    document.querySelectorAll('.theme-install').forEach(function (button) {
      button.addEventListener('click', function (event) {

       if(!event.target.dataset.safe) {
        event.preventDefault();
        event.stopPropagation();
      }

      if(event.target.dataset.disabled) {
        return;
      }
      event.target.dataset.disabled = 'true';

      const themeCard = event.target.closest('.theme');

      const html = `<p>${ionosWPScanThemes.i18n.checking}</p>`;

      const notice = document.createElement('div');
      notice.innerHTML = html;
      notice.classList.add('update-message', 'notice', 'inline', 'notice-warning', 'notice-alt','ionos-theme-issues');
      themeCard?.appendChild(notice);

       jQuery.post(ionosWPScanThemes.ajaxUrl, {
        action: 'ionos-wpscan-instant-check',
        slug: event.target.dataset.slug,
        type: 'theme'
      }).done(function(response) {
        switch(response.data) {
          case 'warnings_found':
            notice.innerHTML = `<p>${ionosWPScanThemes.i18n.warnings_found}</p>`;
            notice.classList.add('notice-info');
            event.target.dataset.safe = 'true';
            event.target.dataset.disabled = 'false';
            break;
          case 'criticals_found':
            notice.innerHTML = `<p>${ionosWPScanThemes.i18n.critical_found}</p>`;
            notice.classList.remove('notice-warning');
            notice.classList.add('notice-error');
            break;
          default:
            notice.innerHTML = `<p>${ionosWPScanThemes.i18n.nothing_found}</p>`;
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
