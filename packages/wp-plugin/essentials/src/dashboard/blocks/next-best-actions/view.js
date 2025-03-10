import apiFetch from '@wordpress/api-fetch';
import './style.css';

document.querySelectorAll('.dismiss-nba').forEach((el) => {
  el.addEventListener('click', async (click) => {
    click.preventDefault();
    const res = await apiFetch({
      path: `ionos/v1/dismiss_nba/${click.target.id}`,
      method: 'GET',
    });
    if (res.status === 'success') {
      const element = click.target.closest('.wp-block-column');
      element.classList.add('dismissed');
      setTimeout(() => {
        element.style.display = 'none';
      }, 250);
    }
  });
});
