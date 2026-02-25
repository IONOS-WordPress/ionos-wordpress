---
'@ionos-wordpress/stretch-extra': patch
---

## add transparent .maintenance support to stretch-extra

- maintenance files are located in wp-content/ instead of WordPress diectory
- supports even wp-content/maintenance.php
- including wp-cli support
  - smoothly integrates into wp-cli by overriding `wp maintenance-mode ...` commands
