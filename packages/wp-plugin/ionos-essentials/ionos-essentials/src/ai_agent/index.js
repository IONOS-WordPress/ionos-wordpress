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

  const chatBotVisible = document.querySelector('#extendify-agent-popout-modal');

  if (!chatBotVisible) {
    innerButton.click();
  }
  clearInterval(interval);
}, 500);
