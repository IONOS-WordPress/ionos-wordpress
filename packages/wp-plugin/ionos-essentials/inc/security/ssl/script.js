document.addEventListener( 'DOMContentLoaded', function () {
  window.setTimeout( function () {
    const ionosSSLCheckButton = document.querySelector(
      '.ionos-ssl-check > button'
    );

    console.log(document.querySelector(
      '.ionos-ssl-check > button'
    ));

    if ( ionosSSLCheckButton === null ) {
      return;
    }

    ionosSSLCheckButton.addEventListener( 'click', function () {
      // eslint-disable-next-line no-undef
      fetch( ionosSSLCheck.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=ionos-ssl-check-dismiss-notice',
      } )
        .then( ( response ) => {
          console.log(response);
          if ( ! response.ok ) {
            throw new Error( 'Error' );
          }
        } )
        .catch( ( error ) => {
          console.log( error ); // eslint-disable-line no-console
        } );
    } );
  }, 1000 );
} );
