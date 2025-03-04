import apiFetch from '@wordpress/api-fetch';

console.log('Next Best Actions block loaded');
document.querySelectorAll('.dismiss-nba').forEach((el) => {
  el.addEventListener('click', async (click) => {
    click.preventDefault();

    const res = await apiFetch({
      path: `ionos/v1/dismiss_nba/${click.target.id}`,
      method: 'GET',
    });

    if (res.status === 'success') {
      const TRANSITION_DURATION = 300;
      const element = click.target.closest('.wp-block-column');

      element.style.transition = `opacity ${TRANSITION_DURATION}ms, transform ${TRANSITION_DURATION}ms`;
      element.style.opacity = '0';
      element.style.transform = 'translateY(-10px)';
      setTimeout(() => {
        element.style.display = 'none';
      }, TRANSITION_DURATION);
    }
  });
});
