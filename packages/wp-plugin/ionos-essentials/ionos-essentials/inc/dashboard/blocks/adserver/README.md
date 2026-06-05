# Adserver Integration

- An ad from the adzone is displayed in the IONOS Dashboard
- In development mode, a test version of the adzone is used
- The embedded script fetches data from the adserver via the JavaScript Fetch API. However, this does not work due to missing CORS headers. To work around this, we proxy the request through a WordPress REST endpoint and redirect the JavaScript window's fetch call to our proxy instead of the original adserver
- The adzone is embedded via an iframe because the adzone script does not work within our Shadow DOM, as it relies on the window object
