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
     message.innerHTML = `<p>Checking for vulnerabilities...</p>`;
     pluginCard.insertBefore(message, pluginCard.firstChild);

     window.setTimeout(function () {
        const isSafe = false;

        if(isSafe) {
          message.innerHTML = `<p>No vulnerabilities found. You can safely install this plugin.</p>`;
          message.classList.remove('notice-warning');
          message.classList.add('notice-success');
          event.target.dataset.safe = 'true';
          event.target.click();
        } else {
          message.innerHTML = `<p>Vulnerabilities found! Please review the plugin details before proceeding.</p>`;
          message.classList.remove('notice-warning');
          message.classList.add('notice-error');
        }
     }, 2000);



   });
 });
});


