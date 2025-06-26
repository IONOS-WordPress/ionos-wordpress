document.addEventListener('DOMContentLoaded', function () {
  // Welcome dialog
  const dashboard = document.querySelector('#wpbody-content').shadowRoot;
  const dialog = dashboard.querySelector('#essentials-welcome_block');
  const closeButton = dashboard.querySelector('.button--primary');

  closeButton.addEventListener('click', function () {
    dialog.close();
    fetch(wpData.restUrl + 'ionos/essentials/dashboard/welcome/v1/closer', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpData.nonce,
      },
      credentials: 'include',
    });
  });

  // Tabs
  const tabButtons = dashboard.querySelectorAll('[data-tab]');
  tabButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();

      let tabButton = event.target;
      if (tabButton.tagName !== 'A') {
        tabButton = tabButton.parentElement;
      }

      const activeTabs = dashboard.querySelectorAll('.ionos-tab.active, .page-tabbar__link--active');
      activeTabs.forEach((tab) => {
        tab.classList.remove('active','page-tabbar__link--active');
      });

      tabButton.classList.add('page-tabbar__link--active');

      let targetTab = event.target?.getAttribute('data-tab');
      if (!targetTab) {
        targetTab = event.target.parentElement?.getAttribute('data-tab');
      }
      dashboard.querySelector(`#${targetTab}`).classList.add('active');
      window.location.hash = targetTab;
    });
  });
  const anchorId = window.location.hash.substring(1);
  if (anchorId) {
    dashboard.querySelector(`[data-tab="${anchorId}"]`)?.click();
  } else {
    dashboard.querySelector('[data-tab]')?.click();
  }
  window.addEventListener('hashchange', () => {
    const newHash = window.location.hash.substring(1);
    if (newHash) {
      dashboard.querySelector(`[data-tab="${newHash}"]`)?.click();
    }
  });

  // NBA
  dashboard.querySelectorAll('.ionos-dismiss-nba').forEach((el) => {
    el.addEventListener('click', async (click) => {
      click.preventDefault();
      dismissItem(click.target);
    });
  });

  const emailAccountLink = dashboard.querySelector('a[data-nba-id="email-account"]');
  if (emailAccountLink) {
    emailAccountLink.onclick = () => {
      dismissItem(emailAccountLink);
    };
  }

  const helpCenterLink = document.querySelector('a[data-nba-id="help-center"]');
  if (helpCenterLink) {
    helpCenterLink.onclick = () => {
      document.querySelector('.extendify-help-center button').click();
      dismissItem(helpCenterLink);
    };
  }

  const dismissItem = async (target) => {
    fetch(wpData.restUrl + 'ionos/essentials/dashboard/nba/v1/dismiss/' + target.dataset.nbaId, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpData.nonce,
      },
      credentials: 'include',
    }).then((response) => {
      if (!response.ok) {
        return;
      }

      dashboard.getElementById(target.dataset.nbaId).classList.add('ionos_nba_dismissed');
      setTimeout(() => {
        dashboard.getElementById(target.dataset.nbaId).remove();

        const nbaCount = dashboard.querySelectorAll('.nba-card').length;
        if (nbaCount === 0) {
          dashboard.querySelector('.ionos_next_best_actions').remove();
        }
      }, 800);
    });
  };

  dashboard.querySelector('#ionos_essentials_install_gml')?.addEventListener('click', function (event) {
    event.target.disabled = true;
    event.target.innerText = wpData.i18n.installing;

    fetch(wpData.restUrl + 'ionos/essentials/dashboard/nba/v1/install-gml', {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpData.nonce,
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === 'success') {
          location.reload();
        }
      });
  });

  dashboard.querySelectorAll('.input-switch').forEach((switchElement) => {
    switchElement.addEventListener('click', function (event) {
      console.log('Switch clicked:', event.target.id);
    });
  });
});
