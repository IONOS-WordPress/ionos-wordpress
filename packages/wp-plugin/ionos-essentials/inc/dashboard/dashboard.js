document.addEventListener("DOMContentLoaded", function() {
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
        }).then(response => {
            if (!response.ok) {
              console.error("Failed to update user meta");
            }
        }).catch(error => {
            console.error("Error:", error);
        });
    });
});
