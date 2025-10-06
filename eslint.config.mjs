import js from '@eslint/js';
import globals from 'globals';
import { includeIgnoreFile } from '@eslint/compat';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import pluginReact from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import eslintPluginImportSort from 'eslint-plugin-simple-import-sort';
import eslintPluginPrettierRecommended from 'eslint-plugin-prettier/recommended';
import eslintPluginImport from 'eslint-plugin-import';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const gitIgnorePath = path.resolve(__dirname, '.gitignore');
const lintIgnorePath = path.resolve(__dirname, '.lintignore');

const configs = [
  js.configs.recommended,
  includeIgnoreFile(gitIgnorePath),
  includeIgnoreFile(lintIgnorePath),
  eslintPluginImport.flatConfigs.recommended,
  pluginReact.configs.flat.recommended,
  reactHooks.configs['recommended-latest'][0],
  eslintPluginPrettierRecommended,
  // {
  //   ignores: ['**/*.md', '**/*.json', '**/*.code-workspace'],
  // },
  {
    files: ['**/*.{js,mjs,cjs,jsx}'],
    plugins: {
      eslintPluginImportSort,
    },
    languageOptions: {
      globals: {
        ...globals['shared-node-browser'],
        ...globals.browser,
        ...globals.node,
      },

      ecmaVersion: 'latest',
      sourceType: 'module',

      // parserOptions: {
      //   ecmaFeatures: {
      //     jsx: true,
      //   },
      // },
    },
    settings: {
      react: {
        version: 'detect',
      },
    },

    rules: {
      'prettier/prettier': ['warn'],
      'react-hooks/rules-of-hooks': ['error'],
      'react-hooks/exhaustive-deps': ['warn'],
      'react/prop-types': ['off'],
      'import/extensions': [
        'error',
        {
          ignore: ['eslint-plugin-prettier/recommended'],
        },
      ],
      'import/no-unresolved': [
        'error',
        {
          ignore: [
            '@wordpress/components',
            '@wordpress/i18n',
            '@wordpress/data',
            '@wordpress/element',
            '@wordpress/hooks',
            '@wordpress/block-editor',
            '@wordpress/blocks',
            '@wordpress/api-fetch',
            '@wordpress/dom-ready',
            '@wordpress/server-side-render',
            '@wordpress/e2e-test-utils-playwright',
            '@playwright/test',
          ],
        },
      ],
      'no-console': ['warn'],
      // drop a warning if jQuery is used
      'no-restricted-globals': ['warn', 'jQuery', '$'],
    },
  },
];

export default configs;
