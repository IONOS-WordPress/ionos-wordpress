import { __ } from "@wordpress/i18n";

import { helloLib } from "@ionos-wordpress/test-lib";

export function helloPlugin() {

  helloLib();

  console.log(__("hello from test lib"));
}
