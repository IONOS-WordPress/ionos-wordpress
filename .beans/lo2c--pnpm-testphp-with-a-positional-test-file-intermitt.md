---
# lo2c
title: pnpm test:php with a positional test file intermittently dies with SIGKILL at 'Installing...'
status: completed
type: bug
priority: normal
created_at: 2026-08-20T12:04:22Z
updated_at: 2026-08-20T12:32:19Z
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

## Root cause (confirmed, not inferred)

`docker events` plus the container's own log pinned it exactly. Timeline of a failing run:

t+0s container create/start
t+3s readiness poll `wp core is-installed` returns 0 (2 earlier polls returned 1)
t+3s `wp config set AUTOMATIC_UPDATER_DISABLED` ok
t+3s phpunit exec starts
t+4s phpunit exec_die 137
t+4s container die 1

And the container log's last three lines:

Success: WordPress installed successfully.
Error: The site you have requested is not installed.
Run `wp core install` to create database tables.

That error is docker-entrypoint.sh's OWN next command (`wp rewrite structure --hard`): phpunit's
bootstrap had already dropped the wp_ tables it shares with the live site. The entrypoint runs
under `set -eu`, so it exited, PID 1 went with it (the `die 1`), and the in-flight `docker exec`
running phpunit was SIGKILLed - exit 137. The container was alive when phpunit died; the `kill`
event after it is test.sh's own cleanup trap.

So the readiness gate was the bug: `wp core is-installed` goes true the moment the entrypoint's
`wp core install` returns, while the rewrite flush, sshd, httpd and AFTER_START are all still
ahead of it. The hypothesis about the syntax-check step was right about the trigger (it skips a
step whose runtime masked the window) but the damage is not a mere race for the database - it
takes the whole container down.

## Fix

- docker-entrypoint.sh: `touch /run/entrypoint-complete` as the very last statement before
  `exec doas -u php /bin/bash -i`, and `rm -f` the same path up front so a `docker restart`
  cannot serve the previous boot's marker.
- scripts/test.sh: await_test_container polls for that marker instead of `wp core is-installed`.
  Also drops a diagnostic when the marker is unsupported, since only `pnpm start` rebuilds the
  image implicitly - `pnpm test:*` reuses whatever is tagged locally, and a stale image would
  otherwise just time out silently after 180s.

Side benefit: the readiness poll no longer bootstraps WordPress at all, so the stray
"WordPress database error: [Table 'wordpress.wp_options' doesn't exist]" that the old
`wp core is-installed` printed into test.sh's stdout is gone too.

## Verification

- the repro (`pnpm run test --use php <LoopTest.php>`), which failed ~50% before: 8/8 then 3/3
  green, 11 consecutive runs
- marker cleared on `docker restart` and re-created ~3s later (checked explicitly)
- the unsupported-image diagnostic fires against the previous image build
- no regressions: `--use php --use e2e` -> phpunit OK (15 tests, 40 assertions) + e2e 28/28;
  `pnpm test:e2e` 28/28; `pnpm test:php` OK
