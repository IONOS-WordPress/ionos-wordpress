---
# lo2c
title: pnpm test:php with a positional test file intermittently dies with SIGKILL at 'Installing...'
status: todo
type: bug
priority: normal
created_at: 2026-08-20T12:04:22Z
updated_at: 2026-08-20T12:04:22Z
---

`pnpm run test --use php <path/to/SomeTest.php>` - the usage the script's own help text documents
("Execute only a single PHPUnit test file") - fails roughly half the time. The run stops right
after phpunit prints `Installing...` and pnpm reports exit 137 (SIGKILL). No positional argument
(plain `pnpm test:php`) has never failed this way.

## Evidence

Three consecutive invocations of the same command:

run 1: exit=0 Tests: 2, Assertions: 2, Skipped: 1.
run 2: exit=137 stops after "Installing...", nothing else
run 3 (.js file): exit=137 same

The file extension is irrelevant - a real `*Test.php` path fails the same way as a `*.spec.js`
path. A passing run also shows a stray WordPress DB error before phpunit starts:

WordPress database error: [Table 'wordpress.wp_options' doesn't exist]
INSERT INTO `wp_options` ... ('cron', ...'ionos-essentials-loop-datacollector-last-access'...)

## Likely cause

With a positional argument, scripts/test.sh skips the target-php-version syntax-check loop
("skipped target php version syntax checks since individual PHPUnit test files are provided as
commandline arguments"). That loop spins up a `php:<version>-cli` container per transpiled plugin
and takes tens of seconds - long enough for the test container's own boot to finish. Skipping it
starts phpunit immediately, so phpunit's install.php (which drops and recreates the wp_ tables -
phpunit/wp-tests-config.php shares the live site's prefix) races the entrypoint's still-running
`wp core install` and the mu-plugin cron write shown above. The syntax-check step was accidentally
serving as the delay that hid this.

ionos.wordpress.await_test_container only waits for
`wp core is-installed --path=/htdocs --skip-plugins --skip-themes`, which goes true as soon as
wp-config.php and the database exist - before docker-entrypoint.sh has finished its install,
rewrite flush and the rest.

## Fix direction

Strengthen the readiness gate so it waits for the entrypoint to be _done_ rather than for the
database to merely exist - e.g. have docker-entrypoint.sh drop a marker file as its last step
before `exec`, and poll for that. Do not rely on the syntax-check step's runtime.

## Not caused by the AFTER_START work (bean z3o3)

Reproduces via `--use php`, which never runs the AFTER_START script; the skip-the-syntax-checks
behaviour and the readiness check both predate that change.

## Repro

pnpm run test --use php packages/wp-mu-plugin/ionos-core/ionos-core/loop/tests/phpunit/LoopTest.php

Repeat a few times - expect a mix of exit 0 and exit 137.
