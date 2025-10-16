// tell eslint that the global variable exists when this file gets executed
/* global ionosWPScanThemes:true */
document.addEventListener('DOMContentLoaded', function () {
  const parent = document.querySelector('.theme-browser');

  if (parent) {
    function highlightThemes() {
      const themes = document.querySelectorAll('[data-slug]');
      if (themes.length === 0) {
        // Retry after a short delay if no themes are found
        // I don't know why this is needed, but the themes are not loaded yet
        window.setTimeout(highlightThemes, 10);
        return;
      }

      themes.forEach(function (theme) {
        if (!ionosWPScanThemes.slugs.includes(theme.dataset.slug)) {
          return;
        }

        const html = `<p>${ionosWPScanThemes.i18n.issues_found}. <span class="ionos-no-activation">${ionosWPScanThemes.i18n.no_activation}</span> <a href="admin.php?page=${ionosWPScanThemes.brand}#tools" class="" type="button">${ionosWPScanThemes.i18n.more_info}</a></p>`;
        // Prevent link click from bubbling
        setTimeout(() => {
          const link = theme.querySelector('a[href="admin.php?page=' + ionosWPScanThemes.brand + '#tools"]');
          if (link) {
            link.addEventListener('click', function (e) {
              e.stopPropagation();
            });
          }
        }, 0);

        const notice = document.createElement('div');
        notice.innerHTML = html;
        notice.classList.add(
          'update-message',
          'notice',
          'inline',
          'notice-warning',
          'notice-alt',
          'ionos-theme-issues'
        );
        theme.appendChild(notice);
      });
    }

    highlightThemes();
  }
});
