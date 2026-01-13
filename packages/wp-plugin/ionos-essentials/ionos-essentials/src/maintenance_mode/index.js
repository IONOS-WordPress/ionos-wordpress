document.addEventListener('DOMContentLoaded', function () {
  const body = document.querySelector('body');
  const dashboard = document.querySelector('#wpbody-content')?.shadowRoot;

  if (!dashboard) {
    return;
  }

  /* global ionosMaintenanceMode:true */
  dashboard.querySelector('#ionos_essentials_maintenance_mode')?.addEventListener('click', function () {
    if (this.checked) {
      body.classList.add('ionos-maintenance-mode');
      dashboard.querySelector('main').classList.add('ionos-maintenance-mode');
      dashboard.querySelector('#ionos-site-preview').src = ionosMaintenanceMode.siteUrlMaintenanceMode;
    } else {
      body.classList.remove('ionos-maintenance-mode');
      dashboard.querySelector('main').classList.remove('ionos-maintenance-mode');
      dashboard.querySelector('#ionos-site-preview').src = ionosMaintenanceMode.siteUrlLive;
    }
  });
});
