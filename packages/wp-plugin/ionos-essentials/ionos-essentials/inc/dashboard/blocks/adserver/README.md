# Adserver Integration

- In development mode, a test version of the adzone is used
- The embedded script fetches data from the adserver via JavaScript fetch API, but this does not work because there are no CORS headers. Therefore, we proxy this through a WordPress REST endpoint and redirect the JavaScript window fetch to call our proxy instead of the original adserver

