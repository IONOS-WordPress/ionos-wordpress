
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import './style.css';

domReady(() => {
  const container = document.getElementById('actions');

  if (container) {
    const items = Array.from(container.getElementsByClassName('action'));

    // Show the first 6 items initially
    let visibleItems = items.slice(0, 6);
    visibleItems.forEach((item) => (item.style.display = 'block'));

    // Hide the remaining items by adding 'hiddenaction' class
    let hiddenItems = items.slice(6);
    hiddenItems.forEach((item) => item.classList.add('hiddenaction'));

    // Dismiss handler
    document.querySelectorAll('.dismiss-nba').forEach((el) => {
      el.addEventListener('click', async (click) => {
        click.preventDefault();
        dismissItem(click.target);
      });
    });

    const emailAccountLink = document.querySelector('a.nba-link[data-nba-id="email-account"]');
    if (emailAccountLink) {
      emailAccountLink.onclick = () => {
        dismissItem(emailAccountLink);
      };
    }

    const helpCenterLink = document.querySelector('a.nba-link[data-nba-id="help-center"]');
    if (helpCenterLink) {
      helpCenterLink.onclick = () => {
        window.parent.document.querySelector('.extendify-help-center button').click();
        dismissItem(helpCenterLink);
      };
    }

    //Dismiss element
    const dismissItem = async (target) => {
      const res = await apiFetch({
        path: `ionos/essentials/dashboard/nba/v1/dismiss/${target.dataset.nbaId}`,
        method: 'POST',
      })

      if (res.status === 'success') {
        const element = target.closest('.wp-block-column');
        element.classList.add('dismissed');

        setTimeout(() => {
          element.style.display = 'none'; // Hide dismissed item

          // Find the next hidden item
          const nextHiddenItem = items.find((item) => item.classList.contains('hiddenaction'));

          // If there is a hidden item, reveal it
          if (nextHiddenItem) {
            nextHiddenItem.classList.remove('hiddenaction'); // Show the next item
            nextHiddenItem.style.display = 'block'; // Make it visible
          }
        }, 250);
      }
    }
  }
});
