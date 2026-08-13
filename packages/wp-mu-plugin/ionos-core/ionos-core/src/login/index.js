/**
 * Track SSO button clicks on the login page
 */
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import './index.css';

async function trackSSOClick(event) {
  event.preventDefault();
  try {
    await apiFetch({
      path: '/ionos/essentials/loop/v1/sso-click',
      method: 'POST',
      data: {
        timestamp: Date.now(),
      },
    });
    await new Promise((resolve) => setTimeout(resolve, 2000));
  } catch (error) {
    console.error('Failed to track SSO click:', error);
  }
  // Continue with the original navigation after tracking
  const target = event.target;
  if (target.href) {
    window.location.href = target.href;
  }
}

/**
 * Initialize event listeners for SSO elements
 */
domReady(() => document.querySelector('#sso-login-link')?.addEventListener('click', trackSSOClick));
