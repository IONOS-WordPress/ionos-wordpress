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
      const tabs = Array.from(dashboard.querySelectorAll('[data-tab]'));
      const activeTab = dashboard.querySelector('.page-tabbar__link--active[data-tab]');
      const activeTabIndex = tabs.indexOf(activeTab);

      document.querySelector('li.current')?.classList.remove('current')
      document.querySelector('[id^="toplevel_page_"] .wp-submenu li:nth-child(' + (activeTabIndex + 2) + ')').classList.add('current')
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
    switchElement.addEventListener('click', async function (event) {
     const option = event.target.dataset.option ?? '';
     const key = event.target.id;
     const value = event.target.checked;
     const description = event.target.dataset.description ?? '';


     try {
        const response = await fetch(wpData.restUrl + 'ionos/essentials/option/set', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce,
          },
          body: JSON.stringify({
            option,
            key,
            value,
          }),
        });

        if (!response.ok) {
          window.EXOS.snackbar.warning("Error updating option " + key);
          return;
        }

        const data = await response.json();

        if (data.value) {
          window.EXOS.snackbar.success(description + ' ' + wpData.i18n.activated);
        } else {
          window.EXOS.snackbar.critical(description + ' ' + wpData.i18n.deactivated);
        }
      } catch (error) {
        window.EXOS.snackbar.warning("Network error updating option " + key);
      }

    });
  });

  dashboard.querySelectorAll('[data-tooltip]').forEach((element) => {
    element.addEventListener('click', function () {
     alert(element.dataset.tooltip);
    });
  });

  dashboard.querySelectorAll('.dialog-closer').forEach((element) => {
    element.addEventListener('click', function () {
      console.log(element);
      dashboard.querySelector('.static-overlay__blocker--active').classList.remove('static-overlay__blocker--active');
      dashboard.querySelector('.static-overlay__container--active').classList.remove('static-overlay__container--active');
    });
  });

  dashboard.querySelector('#learn-more').addEventListener('click', function () {
    dashboard.querySelector('.static-overlay__blocker').classList.add('static-overlay__blocker--active');
    dashboard.querySelector('#learn-more-overlay').classList.add('static-overlay__container--active');
  })

  dashboard.querySelectorAll('[data-slug]').forEach((element) => {
    element.addEventListener('click', function (event) {
      const overlay = dashboard.querySelector('#plugin-install-overlay');

      dashboard.querySelector('.static-overlay__blocker').classList.add('static-overlay__blocker--active');
      overlay.classList.add('static-overlay__container--active');

      const iframe = document.createElement('iframe');
      iframe.style.border = 'none';
      iframe.style.width = '772px';
      iframe.style.height = '554px';
      iframe.style.display = 'none';

      overlay.innerHTML = '<div id="plugin-information-waiting" style="background: white;width:772px; height: 554px;">Waiting for plugin information...</div>';
      overlay.appendChild(iframe);
      iframe.onload = function () {
        overlay.querySelector('#plugin-information-waiting').remove();
        iframe.style.display = 'block';
      };
      iframe.src = `${window.location.origin}/wp-admin/plugin-install.php?tab=plugin-information&plugin=${element.dataset.slug}&`;

    });
  });
});
