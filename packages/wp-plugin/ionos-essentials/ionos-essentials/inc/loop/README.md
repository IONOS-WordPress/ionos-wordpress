# Loop Data Format

## rest endpoint

The provided data can be locally found at
http://localhost:8888/index.php?rest*route=/ionos/essentials/loop/v1/loop-data
Permission to this endpoint is always granted locall due to \_return true* in _rest-permission-callback.php_

## validate json against json schema

`npx ajv-cli validate -s packages/wp-plugin/ionos-essentials/ionos-essentials/inc/loop/schema.json -d packages/wp-plugin/ionos-essentials/ionos-essentials/inc/loop/loop-format-example.json`