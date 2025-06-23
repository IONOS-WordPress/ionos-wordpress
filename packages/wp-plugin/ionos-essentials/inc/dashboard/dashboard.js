document.addEventListener("DOMContentLoaded", function() {
  // Welcome dialog
  const dashboard = document.querySelector("#wpbody-content").shadowRoot
  const dialog = dashboard.querySelector("#essentials-welcome_block");
  const closeButton = dashboard.querySelector(".button--primary");

  closeButton.addEventListener("click", function() {
      dialog.close();
      fetch( wpData.restUrl + 'ionos/essentials/dashboard/welcome/v1/closer', {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": wpData.nonce
        },
        credentials: "include",
      })
  });

  // NBA
  dashboard.querySelectorAll('.ionos-dismiss-nba').forEach((el) => {
    el.addEventListener('click', async (click) => {
      click.preventDefault();
      dismissItem(click.target);
    });
  });

    const emailAccountLink = dashboard.querySelector('a[data-nba-id="email-account"]');
    if (emailAccountLink) {
      emailAccountLink.onclick = () => {
        dismissItem(emailAccountLink);
      };
    }

    const helpCenterLink = document.querySelector('a[data-nba-id="help-center"]');
    if (helpCenterLink) {
      helpCenterLink.onclick = () => {
        document.querySelector('.extendify-help-center button').click();
        dismissItem(helpCenterLink);
      };
    }

  const dismissItem = async (target) => {
    fetch( wpData.restUrl + 'ionos/essentials/dashboard/nba/v1/dismiss/' + target.dataset.nbaId, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": wpData.nonce
      },
      credentials: "include",
   }).then(response => {
      if (!response.ok) {
        return;
      }

       dashboard.getElementById(target.dataset.nbaId).classList.add('ionos_nba_dismissed');
      setTimeout(() => {
        dashboard.getElementById(target.dataset.nbaId).remove();

        const nbaCount = dashboard.querySelectorAll('.nba-card').length;
        if (nbaCount === 0) {
          dashboard.querySelector('.ionos_next_best_actions').remove();
        }
    }, 800);
    });
  };

  dashboard.querySelector("#ionos_essentials_install_gml")?.addEventListener("click", function(event) {

    event.target.disabled = true;
    event.target.innerText =  wpData.i18n.installing;

    fetch(wpData.restUrl +  'ionos/essentials/dashboard/nba/v1/install-gml', {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": wpData.nonce
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === "success") {
        location.reload();
      }
    })
  });

});
