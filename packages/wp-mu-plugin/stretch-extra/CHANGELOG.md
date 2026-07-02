# @ionos-wordpress/stretch-extra

## 1.0.2

### Patch Changes

- a1a694e: dummy change to test the multi-package prerelease pipeline

## 1.0.1

### Patch Changes

- 380a27d: ## add transparent .maintenance support to stretch-extra
  - maintenance files are located in wp-content/ instead of WordPress diectory
  - supports even wp-content/maintenance.php
  - including wp-cli support
    - smoothly integrates into wp-cli by overriding `wp maintenance-mode ...` commands
