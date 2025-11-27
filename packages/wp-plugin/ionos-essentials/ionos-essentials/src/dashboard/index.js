import { __ } from '@wordpress/i18n';

// tell eslint that the global variable exists when this file gets executed
/* global wpData:true */
/* global jQuery:true */
document.addEventListener('DOMContentLoaded', function () {
  // Welcome dialog
  const parent = document.querySelector('.ionos-dashboard');

  if (parent) {
    const dashboard = parent.querySelector('#wpbody-content').shadowRoot;

    dashboard.querySelector('#ionos-welcome-close')?.addEventListener('click', function () {
      dashboard.querySelector('#essentials-welcome_block').close();
      fetch(wpData.restUrl + 'ionos/essentials/dashboard/welcome/v1/closer', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wpData.nonce,
        },
        credentials: 'include',
      });
    });

    dashboard.querySelectorAll('.ionos-popup-dismiss')?.forEach((button) => {
      button.addEventListener('click', function () {
        jQuery.post(wpData.ajaxUrl, { action: 'ionos-popup-dismiss' });
        dashboard.querySelector('#ionos-essentials-popup').close();
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
          tab.classList.remove('active', 'page-tabbar__link--active');
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

    window.addEventListener('hashchange', ionosEssentialsMarkActiveWordPressMenuItem);
    window.addEventListener('load', ionosEssentialsMarkActiveWordPressMenuItem);
    function ionosEssentialsMarkActiveWordPressMenuItem() {
      const hash = window.location.hash.substring(1);
      if (!hash) {
        return;
      }

      dashboard.querySelector(`[data-tab="${hash}"]`)?.click();

      document.querySelector('li.current')?.classList.remove('current');
      const selector = `admin.php?page=${wpData.tenant}` + (hash === 'overview' ? '' : `#${hash}`);
      document.querySelector(`#adminmenu .wp-submenu a[href="${selector}"]`).parentElement.classList.add('current');
    }

    // NBA
    dashboard.querySelectorAll('.ionos-dismiss-nba').forEach((el) => {
      el.addEventListener('click', async (click) => {
        click.preventDefault();
        updateNbaItem(click.target, 'dismissed');
      });
    });

    dashboard.querySelectorAll('a[data-complete-on-click="true"]').forEach((link) => {
      link.addEventListener('click', () => {
        updateNbaItem(link, 'completed');
      });
    });

    dashboard.querySelectorAll('.ionos_finish_setup')?.forEach((button) => {
      button.addEventListener('click', function (event) {
        jQuery.post(wpData.ajaxUrl, {
          action: 'ionos-nba-setup-complete',
          status: event.target.dataset.status,
          _wpnonce: wpData.nonce,
        });

        if (event.target.dataset.status === 'finished') {
          dashboard.querySelector('#ionos_nba_setup_container').remove();
          dashboard.querySelector('#ionos_next_best_actions__setup_complete').style.display = 'block';
          return;
        }

        dashboard.querySelector('.nba-setup').classList.add('ionos_nba_dismissed');
        setTimeout(() => {
          dashboard.querySelector('.nba-setup').remove();
          location.reload();
        }, 800);
      });
    });

    const helpCenterLink = dashboard.querySelector('a[data-nba-id="help-center"]');
    if (helpCenterLink) {
      helpCenterLink.addEventListener('click', () => {
        document.querySelector('.extendify-help-center button').click();
      });
    }

    const updateNbaItem = async (target, status) => {
      fetch(wpData.restUrl + 'ionos/essentials/dashboard/nba/v1/update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wpData.nonce,
        },
        credentials: 'include',
        body: JSON.stringify({ id: target.dataset.nbaId, status }),
      }).then((response) => {
        if (!response.ok) {
          return;
        }

        dashboard.getElementById(target.dataset.nbaId).classList.add('ionos_nba_dismissed');
        setTimeout(() => {
          const item = dashboard.getElementById(target.dataset.nbaId);
          if (item && item.parentElement) {
            item.parentElement.remove();
          }

          const nbaCount = dashboard.querySelectorAll('.nba-active').length;
          if (nbaCount === 0) {
            location.reload();
          }
        }, 800);
      });
    };

    dashboard.querySelector('#ionos_essentials_install_gml')?.addEventListener('click', function (event) {
      event.target.disabled = true;
      event.target.innerText = __('Installing...', 'ionos-essentials');

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
        if (!event.target.matches('input[type="checkbox"]')) {
          return;
        }

        const option = event.target.dataset.option ?? '';
        const key = event.target.id;
        const value = event.target.checked ? 1 : 0; // as false results in a null value in the database
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
            window.EXOS.snackbar.warning('Error updating option ' + key);
            return;
          }

          const data = await response.json();

          if (data.value) {
            window.EXOS.snackbar.success(description + ' ' + __('activated.', 'ionos-essentials'));
          } else {
            window.EXOS.snackbar.critical(description + ' ' + __('deactivated.', 'ionos-essentials'));
          }
        } catch {
          window.EXOS.snackbar.warning('Network error updating option ' + key);
        }
      });
    });

    window.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        dashboard.querySelector('.dialog-closer')?.click();
      }
    });

    dashboard.querySelectorAll('.dialog-closer').forEach((element) => {
      element.addEventListener('click', function () {
        dashboard
          .querySelector('.static-overlay__blocker--active')
          ?.classList.remove('static-overlay__blocker--active');
        dashboard
          .querySelector('.static-overlay__container--active')
          ?.classList.remove('static-overlay__container--active');
      });
    });

    dashboard.querySelector('#learn-more')?.addEventListener('click', function () {
      dashboard.querySelector('.static-overlay__blocker').classList.add('static-overlay__blocker--active');
      dashboard.querySelector('#learn-more-overlay').classList.add('static-overlay__container--active');
    });

    dashboard.querySelector('#restart-ai-sitebuilder')?.addEventListener('click', function () {
      dashboard.querySelector('.static-overlay__blocker').classList.add('static-overlay__blocker--active');
      dashboard.querySelector('#restart-ai-sitebuilder-overlay').classList.add('static-overlay__container--active');
    });

    dashboard.querySelectorAll('[data-slug]').forEach((element) => {
      element.addEventListener('click', function () {
        const overlay = dashboard.querySelector('#plugin-install-overlay');

        dashboard.querySelector('.static-overlay__blocker').classList.add('static-overlay__blocker--active');
        overlay.classList.add('static-overlay__container--active');

        const iframe = document.createElement('iframe');
        iframe.style.border = 'none';
        iframe.style.width = '772px';
        iframe.style.height = '554px';
        iframe.style.display = 'none';

        overlay.innerHTML =
          '<div id="plugin-information-waiting"><div class="loading-spin"></div><p class="paragraph paragraph--large paragraph--bold paragraph--align-center">' +
          __('Loading content ...', 'ionos-essentials') +
          '</p></div>';
        overlay.appendChild(iframe);
        iframe.onload = function () {
          overlay.querySelector('#plugin-information-waiting').remove();
          iframe.style.display = 'block';
        };
        iframe.src = `${window.location.origin}/wp-admin/plugin-install.php?tab=plugin-information&section=changelog&plugin=${element.dataset.slug}&`;
      });
    });

    dashboard.querySelectorAll('[data-wpscan]').forEach((element) => {
      element.addEventListener('click', function (event) {
        element.disabled = true;
        const payload = JSON.parse(element.dataset.wpscan);
        element.innerText =
          payload.action === 'delete' ? __('deleting...', 'ionos-essentials') : __('updating...', 'ionos-essentials');
        event.preventDefault();
        fetch(wpData.restUrl + 'ionos/essentials/wpscan/recommended-action', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce,
          },
          credentials: 'include',
          body: JSON.stringify({
            data: element.dataset.wpscan,
          }),
        })
          .then((response) => {
            if (!response.ok) {
              window.EXOS.snackbar.critical(response.statusText);
              return Promise.reject();
            }
            return response.json();
          })
          .then((data) => {
            element.parentElement.parentElement.remove();
            window.EXOS.snackbar.success(data.data);

            window.setTimeout(() => {
              window.location.reload();
            }, 10000);
          });
      });
    });

    (async () => {
      for (const test of wpData.siteHealthAsyncTests) {
        try {
          let headers = {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce,
          };

          if (test === 'authorization-header') {
            // this test requires an additional nonce
            headers['Authorization'] = 'Basic ' + btoa('user:pwd');
          }

          const response = await fetch(wpData.restUrl + 'wp-site-health/v1/tests/' + test, {
            method: 'GET',
            headers: headers,
            credentials: 'include',
          });
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          const data = await response.json();
          wpData.siteHealthIssueCount[data.status] = parseInt(wpData.siteHealthIssueCount[data.status] ?? 0) + 1;
        } catch (error) {
          // eslint-disable-next-line no-console
          console.error(error);
        }
      }
      // all tests are done, now update the UI
      const totalTests =
        parseInt(wpData.siteHealthIssueCount.good, 10) +
        parseInt(wpData.siteHealthIssueCount.recommended, 10) +
        parseInt(wpData.siteHealthIssueCount.critical, 10) * 1.5;
      const failedTests =
        parseInt(wpData.siteHealthIssueCount.recommended, 10) * 0.5 +
        parseInt(wpData.siteHealthIssueCount.critical, 10) * 1.5;
      const goodTestsRatio = 100 - Math.ceil((failedTests / totalTests) * 100);

      dashboard.querySelector('#bar').style.strokeDashoffset = 565.48 - 565.48 * (goodTestsRatio / 100);

      if (goodTestsRatio <= 80 || wpData.siteHealthIssueCount.critical !== 0) {
        dashboard.querySelector('#site-health-status-message').innerHTML = __('Should be improved', 'ionos-essentials');
        dashboard.querySelector('#bar').classList.add('site-health-color-orange');
      } else {
        dashboard.querySelector('#site-health-status-message').innerHTML = __('Good', 'ionos-essentials');
        dashboard.querySelector('#bar').classList.add('site-health-color-green');
      }

      // set the transient so we do not have to run the tests on every page load
      jQuery.post(wpData.ajaxUrl, {
        action: 'ionos-set-site-health-issues',
        issues: JSON.stringify(wpData.siteHealthIssueCount),
        _wpnonce: wpData.nonce,
      });
    })();

    dashboard.querySelectorAll('.expandable > .panel__item-header').forEach((header) => {
      header.addEventListener('click', () => {
        const item = header.closest('.panel__item');
        const isExpanded = item.classList.contains('panel__item--expanded');

        item.classList.toggle('panel__item--expanded', !isExpanded);
        item.classList.toggle('panel__item--closed', isExpanded);
        item.setAttribute('aria-expanded', String(!isExpanded));
      });
    });

    dashboard.querySelectorAll('[data-track-link]').forEach((element) => {
      element.addEventListener('click', () => {
        ionos_loop_track_click(element.dataset.trackLink);
      });
    });

    function ionos_loop_track_click(anchor) {
      fetch(wpData.restUrl + 'ionos/essentials/loop/v1/click', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wpData.nonce,
        },
        body: JSON.stringify({ anchor }),
        credentials: 'include',
      });
    }
  }
});
