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

      const html = `<p>${ionosEssentialsThemeInstall.i18n.checking}</p>`;

      const notice = document.createElement('div');
      notice.innerHTML = html;
      notice.classList.add('update-message', 'notice', 'inline', 'notice-warning', 'notice-alt','ionos-theme-issues');
      themeCard?.appendChild(notice);

      window.setTimeout(function () {

        // Simulate a random number of issues for demonstration purposes
        let issues = Math.floor(Math.random() * 3);

        switch(issues) {
          case 1:
            notice.innerHTML = `<p>${ionosEssentialsThemeInstall.i18n.warnings_found}</p>`;
            notice.classList.add('notice-info');
            event.target.dataset.safe = 'true';
            event.target.dataset.disabled = 'false';
            break;
          case 2:
            notice.innerHTML = `<p>${ionosEssentialsThemeInstall.i18n.critical_found}</p>`;
            notice.classList.remove('notice-warning');
            notice.classList.add('notice-error');
            break;
          default:
            notice.innerHTML = `<p>${ionosEssentialsThemeInstall.i18n.nothing_found}</p>`;
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
