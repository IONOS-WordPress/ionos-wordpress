import apiFetch from '@wordpress/api-fetch';

const dialog = document.querySelector('dialog');
const closeButton = document.querySelector('dialog#essentials-welcome_block button');

dialog.showModal();

// "Close" button closes the dialog
closeButton.addEventListener('click', (event) => {
  event.preventDefault();
  persistDialog(event.target?.getAttribute('nonce'));
  dialog.close();
});

dialog.addEventListener('click', function (event) {
  var rect = dialog.getBoundingClientRect();
  var isInDialog =
    rect.top <= event.clientY &&
    event.clientY <= rect.top + rect.height &&
    rect.left <= event.clientX &&
    event.clientX <= rect.left + rect.width;
  if (!isInDialog) {
    dialog.close();
  }
});

const persistDialog = ($nonce = 'mops') => {
  apiFetch({
    path: 'ionos/essentials/dashboard/welcome/v1/closer',
    method: 'POST',
    data: {
      nonce: $nonce,
    },
  }).then((response) => {
    console.log('Dialog closed:', response);
  });
};
