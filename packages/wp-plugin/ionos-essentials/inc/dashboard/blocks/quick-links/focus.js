document.addEventListener('DOMContentLoaded', function() {
  const observer = new MutationObserver((mutations, obs) => {
    const iframe = document.querySelector('iframe');
    if (iframe) {
      document.querySelector('.editor-document-tools__document-overview-toggle')?.click();
      iframe.addEventListener('load', function() {
        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
        if ( iframeDocument ) {
          document.querySelector('tr[aria-level=\"1\"] td a')?.click();
        }
      });
      obs.disconnect();
    }
  });

  observer.observe(document, {
    childList: true,
    subtree: true
  });
});
