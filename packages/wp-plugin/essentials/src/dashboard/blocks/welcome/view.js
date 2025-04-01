import apiFetch from '@wordpress/api-fetch';

((dialog) => {
  if (!dialog) {
    return;
  }
  dialog.showModal();
  dialog.querySelector('button').onclick = (event) => {
    event.preventDefault();
    persistDialog();
    dialog.close();
  };
  dialog.onclick = (event) => {
    const rect = dialog.getBoundingClientRect();
    const isInDialog =
      rect.top <= event.clientY &&
      event.clientY <= rect.top + rect.height &&
      rect.left <= event.clientX &&
      event.clientX <= rect.left + rect.width;
    if (!isInDialog) {
      persistDialog();
      dialog.close();
    }
  };

  const persistDialog = () => {
    apiFetch({
      path: 'ionos/essentials/dashboard/welcome/v1/closer',
      method: 'POST',
    });
  };
})(document.querySelector('dialog'));
