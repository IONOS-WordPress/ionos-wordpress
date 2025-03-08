import apiFetch from '@wordpress/api-fetch';

console.log('Next Best Actions block loaded');
document.querySelectorAll('.dismiss-nba').forEach((el) => {
  el.addEventListener('click', async (click) => {
    console.log(wp.ajax);
    // alert(`Dismiss NBA ${click.target.id}`);
    const res = await apiFetch({
      path: `ionos/v1/dismiss_nba/${click.target.id}`,
      method: 'GET',
    });
    if (res.status === 'success') {
      // TODO
      click.target.parentElement.parentElement.parentElement.parentElement.style.display = 'none';
    }
  });
});
