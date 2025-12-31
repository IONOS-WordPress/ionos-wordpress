/**
 * Track SSO button clicks on the login page
 */
(function() {
  'use strict';

  function trackSSOClick() {
    // Send tracking request to REST API
    fetch(ionosLoginTracking.restUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': ionosLoginTracking.nonce
      },
      body: JSON.stringify({
        timestamp: Date.now()
      })
    }).catch(function(error) {
      console.error('Failed to track SSO click:', error);
    });
  }

  // Wait for DOM to be ready
  function init() {
    // Look for SSO button/link by checking for ionos_oauth_authenticate in href
    const ssoLinks = document.querySelectorAll('a[href*="ionos_oauth_authenticate"]');

    ssoLinks.forEach(function(link) {
      link.addEventListener('click', trackSSOClick);
    });

    // Also track any buttons that trigger SSO authentication
    const ssoButtons = document.querySelectorAll('button[onclick*="ionos_oauth"], input[onclick*="ionos_oauth"]');

    ssoButtons.forEach(function(button) {
      button.addEventListener('click', trackSSOClick);
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
