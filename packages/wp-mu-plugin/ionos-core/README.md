# IONOS Core MU-Plugin

- updates itself
- loop functions
- marketplace functions

## Installation

Run these commands on the remote server.

```
cd wp-content/mu-plugins
curl -L -o ionos-core.zip "https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Fionos-core%400.3.1/ionos-core-0.3.1-php7.4.zip"
unzip ionos-core.zip
rm ionos-core.zip
mv ionos-core/ionos-core.php .
mv ionos-core foo
mv foo/ionos-core .
rm -rf foo
```
