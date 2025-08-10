import './plugin-install.css';
import { __ } from '@wordpress/i18n';
import { addResultNotice, disableButton, getSeverityBySlug, getStatusByButton } from './utils.js';

/**
 * This script is called in both plugin-install.php screens ("add plugin" -> table and "info" -> potentially iframe)
 *
 * adds clickhandler that scan plugins before install and displays scan progress & results
 *
 * the clickhandler is being registered one element below the existing "install" clickhandler in both cases
 * so we have control over whether to fire the install event after the scan or not
 */

/**
 * Clickhandler for "Install Now" Button.
 *
 * @param {Event} event
 */
function handleInstallButtonClick(event) {
  const eInstallButton = event.target;
  // prevent scanning twice
  if (eInstallButton.dataset.alreadyClicked) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();
  if (eInstallButton.disabled) {
    return;
  }
  eInstallButton.dataset.alreadyClicked = true;
  const originalText = eInstallButton.textContent;
  eInstallButton.textContent = __('Checking …', 'ionos-security');
  eInstallButton.classList.add('updating-message');
  const slug = eInstallButton.dataset.slug;

  wp.ajax
    .send('scan-plugin', {
      type: 'GET',
      data: {
        slug,
        _ajax_nonce: wp.updates.ajaxNonce,
      },
    })
    .then((res) => {
      const severity = res.severity;
      // window.parent is equal to window for non iframe cases.
      // keep scanresult on (parent) window.
      window.parent.wpScan.push({
        slug,
        severity,
      });

      // add Result to current Screen.
      addResultNotice(
        severity,
        getStatusByButton(eInstallButton),
        isInfo ? document.querySelector('#section-holder') : document.querySelector('.plugin-card-' + slug)
      );
      if (isIframe) {
        addResultNotice(
          severity,
          getStatusByButton(eInstallButton),
          window.parent.document.querySelector('.plugin-card-' + slug)
        );
      }

      // reset Button state
      eInstallButton.classList.remove('updating-message');
      eInstallButton.textContent = originalText;

      if (severity === 'none') {
        // install immediately, but wp.updates enqueues and installs one by one
        wp.updates.installPlugin({ slug });
      } else if (severity === 'high') {
        // disable Button for plugin table and potentially in iframe
        disableButton(eInstallButton);
        if (isIframe) {
          disableButton(window.parent.document.querySelector('.plugin-card-' + slug + ' .install-now.button'));
        }
      }
    });
}

/**
 * Adds Clickhandler to the provided element
 *
 * @param {Element} ePluginTable
 */
function manipulatePluginTable(ePluginTable) {
  ePluginTable.addEventListener('click', (event) => {
    // a single clickhandler is added on the plugin table. check if click is on "Install Now" button
    if (event.target.matches?.('a.button.install-now')) {
      handleInstallButtonClick(event);
    }
  });
  for (const ePluginCard of ePluginTable.querySelectorAll('.plugin-card')) {
    // slug is found in classList "plugin-card-{slug}"
    const slug = [...ePluginCard.classList].find((_) => _.startsWith('plugin-card-')).replace(/plugin-card-/, '');

    const knownSeverity = getSeverityBySlug(slug);

    if (knownSeverity) {
      const eInstallButton = ePluginCard.querySelector('.plugin-action-buttons .button');
      const status = getStatusByButton(eInstallButton);
      addResultNotice(knownSeverity, status, ePluginCard);

      eInstallButton.dataset.alreadyClicked = true;
      if (knownSeverity === 'high') {
        // disable Button for plugin table and potentially in iframe
        disableButton(eInstallButton);
        if (isIframe) {
          disableButton(window.parent.document.querySelector('.plugin-card-' + slug + ' .install-now.button'));
        }
      }
    }
  }
}

// find out active screen
const urlParams = new URLSearchParams(window.location.search);
const isInfo = urlParams.get('tab') === 'plugin-information';
const isIframe = window.parent !== window;

if (isInfo) {
  // check if plugin is already scanned
  const slug = urlParams.get('plugin');
  const knownSeverity = getSeverityBySlug(slug);
  if (knownSeverity) {
    const eInstallButton = document.querySelector('#plugin-information-footer .button');

    addResultNotice(knownSeverity, getStatusByButton(eInstallButton), document.querySelector('#section-holder'));

    if (knownSeverity === 'high') {
      disableButton(document.querySelector('#plugin_install_from_iframe'));
    }
  } else {
    document.querySelector('#plugin_install_from_iframe')?.addEventListener('click', handleInstallButtonClick);
  }
} else {
  // "add Plugin" screen with table
  // for the initial pageload
  manipulatePluginTable(document.querySelector('.wp-list-table.plugin-install'));

  // for all dynamic changes in the plugin table (e.g. triggered through search)
  // TODO: set eslint config correctly so that browser Interfaces are recognized

  new MutationObserver((mutationList) => {
    for (const mutation of mutationList) {
      if (mutation.type === 'childList') {
        for (const node of mutation.addedNodes) {
          if (node.matches?.('.wp-list-table.plugin-install')) {
            manipulatePluginTable(node);
          }
        }
      }
    }
  }).observe(document.querySelector('#plugin-filter'), {
    childList: true,
  });
}
