/* global MutationObserver */
import './wpscan-themes.css';
import { __ } from '@wordpress/i18n';
import { addResultNotice, disableButton, getSeverityBySlug, getStatusByButton } from './utils.js';

/**
 * This script adds clickhandlers that scan plugins before install and displays scan progress & results
 * dynamic changes (search, opening single-theme overlays) are watched and treated by MutationObservers
 */

/**
 * Handles the click event for the "Install Now" button.
 * Prevents multiple scans, updates button text, sends an AJAX request to scan the theme,
 * and processes the scan results.
 *
 * @param {Event} event - The click event object.
 */
function installNowClickHandler(event) {
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
  const isOverlay = event.target.parentNode.classList.contains('wp-full-overlay-header');

  wp.ajax
    .send('scan-theme', {
      type: 'GET',
      data: {
        slug,
        _ajax_nonce: wp.updates.ajaxNonce,
      },
    })
    .then((res) => {
      const severity = res.severity;
      window.wpScan.push({ slug, severity });
      // add Notice in overlay
      if (isOverlay) {
        addResultNotice(severity, 'scanned', document.querySelector('.install-theme-info'), '.theme-screenshot');
      }
      // add notice in theme-browser
      addResultNotice(severity, 'scanned', document.querySelector(`.theme[data-slug="${slug}"]`), '.theme-details');

      // reset Button
      eInstallButton.classList.remove('updating-message');
      eInstallButton.textContent = originalText;

      if (severity === 'none') {
        // install immediately
        wp.updates.installTheme({ slug });
      } else if (severity === 'high') {
        // update Button for theme table and potentially in overlay
        disableButton(eInstallButton);
        if (isOverlay) {
          disableButton(document.querySelector(`.theme[data-slug="${slug}"] a.button.theme-install`));
        }
      }
    });
}

/**
 * Observes mutations in the theme list and adds click handlers to new themes.
 * Also adds result notices and disables buttons based on known scan severity.
 *
 * @param {MutationRecord[]} mutationList - List of mutations observed.
 */
function manipulateThemes(mutationList) {
  for (const mutation of mutationList) {
    if (mutation.type === 'childList') {
      for (const eThemeDiv of mutation.addedNodes) {
        const slug = eThemeDiv.dataset?.slug;
        // only treat theme divs
        if (!slug) {
          continue;
        }
        const knownSeverity = getSeverityBySlug(slug);
        const ePrimaryButton = eThemeDiv.querySelector('.button-primary');
        if (knownSeverity) {
          const themeStatus = getStatusByButton(ePrimaryButton);
          addResultNotice(
            knownSeverity,
            themeStatus,
            document.querySelector(`.theme[data-slug="${slug}"]`),
            '.theme-details'
          );
          if (knownSeverity === 'high') {
            disableButton(ePrimaryButton);
            const ePreviewButton = eThemeDiv.querySelector('.button.load-customize');
            if (themeStatus === 'installed' && ePreviewButton) {
              disableButton(ePreviewButton);
            }
          }
        } else {
          if (ePrimaryButton?.classList.contains('activate')) {
            continue;
          }
          ePrimaryButton?.addEventListener('click', installNowClickHandler);
        }
      }
    }
  }
}

/**
 * Observes mutations in the theme install overlay and adds click handlers to the install button.
 * Also adds result notices and disables buttons based on known scan severity.
 */
function manipulateOverlay() {
  // take slug from searchParams because it can't be found anywhere else
  const slug = new URLSearchParams(window.location.search).get('theme');
  const knownSeverity = getSeverityBySlug(slug);
  const ePrimaryButton = document.querySelector('.theme-install-overlay .button-primary');
  if (knownSeverity) {
    const themeStatus = getStatusByButton(ePrimaryButton);
    addResultNotice(knownSeverity, themeStatus, document.querySelector('.install-theme-info'), '.theme-screenshot');
    if (knownSeverity === 'high') {
      disableButton(ePrimaryButton);
    }
  } else {
    if (ePrimaryButton?.classList.contains('activate')) {
      return;
    }
    ePrimaryButton.addEventListener('click', installNowClickHandler);
  }
}

// observe initial render step just once to get .themes div
// this avoids having to observe the subtree
new MutationObserver((mutationList, observer) => {
  for (const mutation of mutationList) {
    if (mutation.target.classList.contains('rendered') && !mutation.oldValue.includes('rendered')) {
      // watch themes div (updates e.g. on search)
      new MutationObserver(manipulateThemes).observe(document.querySelector('.themes.wp-clearfix'), {
        childList: true,
      });
      // disconnect after first mutation
      observer.disconnect();
    }
  }
}).observe(document.querySelector('.theme-browser'), {
  attributeFilter: ['class'],
  attributeOldValue: true,
});

// watch install overlay (for theme preview view)
new MutationObserver(manipulateOverlay).observe(document.querySelector('.theme-install-overlay'), {
  childList: true,
});
