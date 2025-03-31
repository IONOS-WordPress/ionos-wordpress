((dialog) => {
  if (!dialog) {
    return;
  }
  dialog.showModal();
  // "Close" button closes the dialog
  dialog.querySelector('button').addEventListener('click', () => dialog.close());
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
})(document.querySelector('dialog'));
