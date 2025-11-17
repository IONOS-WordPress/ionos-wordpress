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

  if (!isDisabled) {
    innerButton.click();
  }
  clearInterval(interval);
}, 500);
