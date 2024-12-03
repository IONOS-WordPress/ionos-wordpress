# script naming convention

- scripts marked as executable are intended to be run directly from the command line

  mostly they are wrapped by pnpm scripts to provide a more consistent interface

- executable marked scripts starting with `_` are not intended to be started directly from the command line

  they are used internally (for example in github workflows)

  _Think of it as non-public scripts._

- scripts marked as non-executable are intended to be included in other scripts
