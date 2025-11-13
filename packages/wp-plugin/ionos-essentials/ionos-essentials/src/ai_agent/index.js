const interval = setInterval(() => {
  const agentItem = document.querySelector('.extendify-agent');
  if (!agentItem) {
    return;
  }

  const innerButton = agentItem.querySelector('button, a');
  if (!innerButton) {
    clearInterval(interval);
    return;
  }

  const isDisabled = innerButton.classList.contains('opacity-60');
  const urlParams = new URLSearchParams(window.location.search);
  const highlight = urlParams.get('ionos-highlight');

  if (!isDisabled && highlight) {
    if (window.jQuery) {
      window.jQuery(innerButton).trigger('click');
    } else {
      // fallback for jQuery: Clicking via mouse event
      const event = new MouseEvent('click', { bubbles: true, cancelable: true });
      innerButton.dispatchEvent(event);
    }
  }

  clearInterval(interval);
}, 500);
