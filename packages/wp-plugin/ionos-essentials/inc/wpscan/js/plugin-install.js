document.addEventListener('DOMContentLoaded', function () {
 document.querySelectorAll('.install-now').forEach(function (button) {
   button.addEventListener('click', function (event) {
    if(!event.target.dataset.safe) {
      event.preventDefault();
      event.stopPropagation();
    }

     if(event.target.dataset.disabled) {
       return;
     }
     event.target.dataset.disabled = 'true';

     const pluginCard = event.target.closest('.plugin-card');

     const message = document.createElement('div');
     message.classList.add('notice', 'notice-alt', 'notice-warning', 'inline');
     message.innerHTML = `<p>${ionosEssentialsPlugins.i18n.checking}</p>`;
     pluginCard?.insertBefore(message, pluginCard.firstChild);

     window.setTimeout(function () {
        // toDo: ask middleware for issues
        // Simulate a random number of issues for demonstration purposes
        // In a real scenario, you would replace this with an AJAX call to your server
        // to check for plugin vulnerabilities or issues.
        const issues = Math.floor(Math.random() * 3);

        switch(issues) {
          case 1:
            message.innerHTML = `<p>${ionosEssentialsPlugins.i18n.warnings_found}</p>`;
            message.classList.add('notice-info');
            event.target.dataset.safe = 'true';
            event.target.dataset.disabled = 'false';
            break;
          case 2:
            message.innerHTML = `<p>${ionosEssentialsPlugins.i18n.critical_found}</p>`;
            message.classList.remove('notice-warning');
            message.classList.add('notice-error');
            break;
          default:
            message.innerHTML = `<p>${ionosEssentialsPlugins.i18n.nothing_found}</p>`;
            message.classList.remove('notice-warning');
            message.classList.add('notice-success');
            event.target.dataset.safe = 'true';
            event.target.click();
            break;
        }

     }, 2000);
   });
 });
});


