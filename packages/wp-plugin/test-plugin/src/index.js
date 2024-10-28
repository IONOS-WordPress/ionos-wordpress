import { __ } from "@wordpress/i18n";

import { hello as hello_from_lib } from "@ionos-wordpress/test-lib";

export function hello() {

  hello_from_lib();

  console.log(__("hello from packages/wp-plugin/test-plugin/src/index.js", "test-plugin"));
}


hello();
