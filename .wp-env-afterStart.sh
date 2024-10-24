#!/usr/bin/env bash

#
# this script is used to customize the created wp-env instance
#

set -eo pipefail

WPENV_INSTALLPATH="$(realpath --relative-to $(pwd) $(pnpm exec wp-env install-path))"

# remove dolly demo plugin
rm -f $WPENV_INSTALLPATH/{tests-WordPress,WordPress}/wp-content/plugins/hello.php

for prefix in '' 'tests-' ; do
  # this wp-cli configuration file needs to be created to enable wp-cli to work with the apache mod_rewrite module
  pnpm exec wp-env run ${prefix}cli sh -c 'echo -e "apache_modules:\n  - mod_rewrite" > /var/www/html/wp-cli.yml'

  # The wp rewrite flush command regenerates the rewrite rules for your WordPress site, which includes refreshing the permalinks.
  pnpm exec wp-env run ${prefix}cli wp --quiet rewrite flush
  # The wp rewrite structure command updates the permalink structure. --hard also updates the .htaccess file
  pnpm exec wp-env run ${prefix}cli wp --quiet rewrite structure '/%postname%' --hard

  # Updates an option value for example the value of Simple page is id = 2
  pnpm exec wp-env run ${prefix}cli wp option update page_on_front 2
  # Update the page as front page by default.
  pnpm exec wp-env run ${prefix}cli wp option update show_on_front page
done

# generate .vscode/launch.json
(
  # echoes comma spearated list of plugins
  function plugins {
    for PLUGIN in $(find packages/wp-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      echo "        \"/var/www/html/wp-content/plugins/${PLUGIN}\":\"\${workspaceFolder}/packages/wp-plugin/${PLUGIN}\","
    done
  }

  # echoes comma spearated list of plugins
  function themes {
    for THEME in $(find packages/wp-theme -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      echo "        \"/var/www/html/wp-content/themes/${THEME}\":\"\${workspaceFolder}/packages/wp-theme/${THEME}\","
    done
  }

  # generate launch configuration
  cat << EOF > '.vscode/launch.json'
  {
    // THIS FILE IS MACHINE GENERATED by .wp-env-afterStart.sh - DO NOT EDIT!
    // If you need to confgure additional launch configurations consider defining them in a vscode *.code-workspace file
    "version": "0.2.0",
    "configurations": [
      {
        "name": "wp-env",
        "type": "php",
        "request": "launch",
        "port": 9003,
        "stopOnEntry": false, // set to true for debugging this launch configuration
        "log": false,         // set to true to get extensive xdebug logs
        "pathMappings": {
$(plugins)
$(themes)
          "/var/www/html": "\${workspaceFolder}/${WPENV_INSTALLPATH}/WordPress",
        }
      }
    ]
  }
EOF
)

# generate settings.json
cat << EOF > '.vscode/settings.json'
{
  // THIS FILE IS MACHINE GENERATED by .wp-env-afterStart.sh - DO NOT EDIT!
  // If you need to confgure additional launch configurations consider defining them in a vscode *.code-workspace file
  "intelephense.files.exclude": [
    "**/.git/**",
    "**/.svn/**",
    "**/.hg/**",
    "**/CVS/**",
    "**/.DS_Store/**",
    "**/node_modules/**",
    "**/bower_components/**",
    "**/vendor/**/{Tests,tests}/**",
    "**/.history/**",
    "**/vendor/**/vendor/**",
    "**/dist/**",
    "**/buid/**",
  ],
  "search.exclude": {
    "**/node_modules": true,
    "**/build/**" : true,
    "**/build-module/**" : true
  },
  "intelephense.environment.phpVersion": "8.3",
  "intelephense.environment.includePaths": [
    "${WPENV_INSTALLPATH}/WordPress"
  ],
  "git.autoRepositoryDetection": false,
  "json.schemas": [
    {
      "fileMatch": ["jsonschema.json", "*schema.json"],
      "url": "https://json-schema.org/draft/2019-09/schema"
    },
    {
      "fileMatch": ["tsconfig.json"],
      "url": "https://json.schemastore.org/tsconfig"
    }
  ],
  "[html]": {
    "editor.formatOnSave": false
  }
}
EOF
