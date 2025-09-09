// File: site-health-status.js
(function($) {
  $(document).ready(function() {
    console.log('Triggering Site Health async tests...');

    fetch('/wp-json/wp-site-health/v1/tests/async-direct', {
      method: 'GET',
      headers: {
        'X-WP-Nonce': SiteHealthData.restNonce
      }
    })
    .then(res => res.json())
    .then(data => {
      if (!data?.good || typeof data.good !== 'number') {
        throw new Error('Unexpected Site Health result');
      }

      let status = 'good';

      if (data.critical > 0) {
        status = 'critical';
      } else if (data.recommended > 0) {
        status = 'recommended';
      }

      console.log('Saving site health status:', status);

      return $.post(SiteHealthData.ajaxUrl, {
        action: 'save_site_health_status',
        status,
        nonce: SiteHealthData.ajaxNonce
      });
    })
    .then(resp => {
      console.log('Saved:', resp);
    })
    .catch(err => {
      console.error('❌ Error fetching Site Health data:', err);
    });
  });
})(jQuery);
