import prettier from 'eslint-plugin-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import simpleImportSort from 'eslint-plugin-simple-import-sort';
import _import from 'eslint-plugin-import';
import { fixupPluginRules, includeIgnoreFile } from '@eslint/compat';
import globals from 'globals';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import js from '@eslint/js';
import { FlatCompat } from '@eslint/eslintrc';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all,
});

const gitIgnorePath = path.resolve(__dirname, '.gitignore');
const lintIgnorePath = path.resolve(__dirname, '.lintignore');

export default [
  includeIgnoreFile(gitIgnorePath),
  includeIgnoreFile(lintIgnorePath),
  {
    ignores: ['**/*.md', '**/*.json', '**/*.code-workspace'],
  },
  ...compat.extends('prettier'),
  {
    plugins: {
      prettier,
      react,
      'react-hooks': fixupPluginRules(reactHooks),
      'simple-import-sort': simpleImportSort,
      import: fixupPluginRules(_import),
    },

    languageOptions: {
      globals: {
        ...globals['shared-node-browser'],
        ...globals.browser,
        ...globals.node,
      },

      ecmaVersion: 'latest',
      sourceType: 'module',

      parserOptions: {
        ecmaFeatures: {
          jsx: true,
        },
      },
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
      'react/prop-types': ['warn'],
      'import/extensions': ['error', 'always'],
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
          ],
        },
      ],
      'no-console': ['warn'],
    },
  },
];
