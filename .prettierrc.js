module.exports = {
  semi: true,
  tabWidth: 2,
  singleQuote: true,
  printWidth: 120,
  bracketSameLine: true,
  trailingComma: 'es5',
  overrides: [
    /*
      # {
      #   "files": ["*.json5"],
      #   "options": { "singleQuote": false, "quoteProps": "preserve" },
      # },
      */
    { files: ['*.yml'], options: { singleQuote: false } },
    {
      files: ['*.php'],
      options: {
        // see  https://github.com/prettier/plugin-php?tab=readme-ov-file#visual-studio-code
        // 8.3 is currently not supported by the plugin
        phpVersion: '8.2',
        braceStyle: '1tbs',
        parser: 'php',
      },
    },
  ],
  plugins: ['@prettier/plugin-php'],
};
