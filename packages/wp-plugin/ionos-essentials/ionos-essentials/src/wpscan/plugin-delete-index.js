/**
 * Remove vulnerability notices when a plugin is deleted via AJAX.
 */
document.addEventListener('DOMContentLoaded', () => {
  const pluginList = document.querySelector('#the-list');

  if (!pluginList) {
    return;
  }

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.removedNodes.forEach((node) => {
        if (node.nodeType === 1 && node.tagName === 'TR') {
          const slug = node.getAttribute('data-slug');

          if (slug) {
            const noticeRow = pluginList.querySelector(`.ionos-wpscan-notice[data-parent-slug="${slug}"]`);
            if (noticeRow) {
              noticeRow.remove();
            }
          }
        }
      });
    });
  });

  // Start watching the table body for changes
  observer.observe(pluginList, {
    childList: true,
  });
});
