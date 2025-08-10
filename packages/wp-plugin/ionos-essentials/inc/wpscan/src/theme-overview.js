/* global MutationObserver */
import './wpscan-themes.css';
import { addResultNotice, disableButton, getSeverityBySlug } from './utils.js';

/**
 * Observes mutations in the theme list
 * Also adds result notices and disables buttons based on known scan severity.
 *
 */
function manipulateThemes() {
  for (const eThemeDiv of document.querySelectorAll('.theme-browser .themes .theme')) {
    const slug = eThemeDiv.dataset?.slug;
    // only treat theme divs
    if (!slug) {
      continue;
    }
    const isActiveTheme = eThemeDiv.classList.contains('active');
    const severity = getSeverityBySlug(slug);
    addResultNotice(
      severity,
      isActiveTheme ? 'active' : 'installed',
      document.querySelector(`.theme[data-slug="${slug}"]`),
      '.more-details'
    );
    // active theme buttons stay untouched
    if (!isActiveTheme && severity === 'high') {
      const eActivateButton = eThemeDiv.querySelector('a.button.activate');
      disableButton(eActivateButton);
      const eCustomizeButton = eThemeDiv.querySelector('a.button.load-customize');
      disableButton(eCustomizeButton);
    }
  }
}

/**
 * Observes mutations in the theme install overlay and adds click handlers to the install button.
 * Also adds result notices and disables buttons based on known scan severity.
 * If only one theme is rendered the overlay is rendered in a different position, but still handled by this function.
 */
function manipulateOverlay() {
  const eThemeOverlay = document.querySelector('.theme-overlay');
  if (eThemeOverlay.childElementCount === 0) {
    return;
  }

  const isActiveTheme =
    eThemeOverlay.classList.contains('active') || eThemeOverlay.firstChild?.classList.contains('active');

  // take slug from searchParams because it can't be found anywhere else
  // if only one theme is installed, treat the special case
  const slug =
    new URLSearchParams(window.location.search).get('theme') || document.querySelector('.theme.active').dataset.slug;

  const severity = getSeverityBySlug(slug);
  addResultNotice(
    severity,
    isActiveTheme ? 'active' : 'installed',
    eThemeOverlay.querySelector('.theme-info'),
    '.theme-autoupdate'
  );

  // active theme buttons stay untouched
  if (!isActiveTheme && severity === 'high') {
    const eActivateButton = eThemeOverlay.querySelector('.inactive-theme a.button.activate');
    disableButton(eActivateButton);
    const eCustomizeButton = eThemeOverlay.querySelector('.inactive-theme a.button.load-customize');
    disableButton(eCustomizeButton);
  }
}

// watch theme browser only for the initial update. Before it's empty.
new MutationObserver((mutationList, observer) => {
  for (const mutation of mutationList) {
    if (mutation.target.classList.contains('rendered') && !mutation.oldValue.includes('rendered')) {
      manipulateThemes();
      // watch themes div (updates e.g. on search)
      new MutationObserver(manipulateThemes).observe(document.querySelector('.theme-browser .themes'), {
        childList: true,
      });
      // edge case: theme-browser has overlay child if only one theme is installed
      if (document.querySelector('.theme-overlay.active')) {
        manipulateOverlay();
      }

      // disconnect after first mutation
      observer.disconnect();
    }
  }
}).observe(document.querySelector('.theme-browser'), {
  attributeFilter: ['class'],
  attributeOldValue: true,
});

// watch overlay (for theme details view)
new MutationObserver(manipulateOverlay).observe(document.querySelector('.theme-overlay'), {
  childList: true,
});
