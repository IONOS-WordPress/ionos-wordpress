# Maintenance Mode (`stretch-extra`)

## What it is good for

This feature puts a WordPress site into maintenance mode by serving an HTTP 503
response with a `Retry-After` header to all incoming web requests. It is
specifically designed for hosting platforms where the **WordPress root directory
is read-only**, which makes WordPress core's built-in `.maintenance` file
mechanism unavailable.

Use cases:

- Automated maintenance window during plugin / theme / core updates (activated
  automatically by the upgrade hooks)
- Manual maintenance window triggered via WP-CLI from a deployment script or
  cron job
- Safety net: maintenance mode auto-expires after 10 minutes even if
  deactivation is never called explicitly

---

## How it works / workflow

### Activation

1. A **symlink** is created at `wp-content/maintenance.php` pointing to the
   handler file bundled with the mu-plugin
   (`stretch-extra/inc/maintenance/maintenance.php`).
2. A **sentinel file** is written to `wp-content/.stretch-extra-maintenance`.
   It contains a single PHP line that sets `$upgrading` to the current Unix
   timestamp:
   ```php
   <?php $upgrading = 1700000000; ?>
   ```
3. Re-activating while already active **resets the expiry timer** (the sentinel
   file is overwritten with the new timestamp).

### Request handling

On every web request the mu-plugin entry point (`stretch-extra/index.php`)
calls `include_once` on `wp-content/maintenance.php` if the symlink exists.
That file registers a callback on the `muplugins_loaded` hook — the earliest
available WordPress hook — which:

1. Reads and `include`s the sentinel file to obtain the `$upgrading` timestamp.
2. Checks whether the mode has **expired** (`time() - $upgrading >= 600`).
3. Applies the `enable_maintenance_mode` filter (mirrors WordPress core
   behaviour so third-party code can still bypass it).
4. If active and not expired: sends HTTP 503 + `Retry-After: 600` and calls
   `wp_die()`. Plugins and themes are never loaded.

WP-CLI is **never blocked**: when `PHP_SAPI === 'cli'` the handler returns
immediately, so CLI commands can always run even with maintenance mode active.

### Deactivation

Both the symlink and the sentinel file are deleted. Safe to call when already
inactive (no-op).

### Automatic integration with WordPress updates

| Hook | Action |
|---|---|
| `upgrader_pre_install` | `activate()` — turns on maintenance mode before an update or install begins |
| `upgrader_process_complete` | `deactivate()` — turns off maintenance mode after the update finishes |

This means maintenance mode is always cleaned up after an update completes,
even if it was triggered outside the WP-CLI command (e.g. via the WP admin or
another tool).

---

## Files created at runtime

| Path | Description |
|---|---|
| `wp-content/maintenance.php` | Symlink → `stretch-extra/inc/maintenance/maintenance.php`. WordPress serves this file on maintenance requests. |
| `wp-content/.stretch-extra-maintenance` | PHP sentinel file. Contains `$upgrading = <unix-timestamp>;`. Read by the handler to determine whether the mode is active and whether it has expired. |

Both files are removed on deactivation.

---

## Source files

| File | Description |
|---|---|
| `index.php` | Core logic: `activate()`, `deactivate()`, `_get_maintenance_mode_status()`. Also registers the `upgrader_pre_install` / `upgrader_process_complete` hooks. Conditionally loads `wp-cli.php` when running under WP-CLI. |
| `maintenance.php` | Request handler. Symlinked into `wp-content/` on activation. Registers the `muplugins_loaded` hook that enforces the 503 response. No-op in CLI context. |
| `wp-cli.php` | WP-CLI command class. Overrides the built-in `wp maintenance-mode` command with an implementation that uses the symlink / sentinel mechanism. |

---

## Difference to the ionos-essentials maintenance feature

| Aspect | stretch-extra (`this feature`) | ionos-essentials |
|---|---|---|
| **Purpose** | Technical maintenance during update processes; read-only-root compatible | User-facing "coming soon" / site-under-construction page |
| **Trigger** | WP-CLI command or automatic upgrade hooks | WordPress admin option (`ionos_essentials_maintenance_mode`) |
| **Hook depth** | `muplugins_loaded` — plugins and themes are never loaded | `init` — full WordPress bootstrap has already run |
| **HTTP response** | 503 + `Retry-After` header (crawler-safe) | 302 redirect to `/maintenance` page |
| **Logged-in users** | Blocked like everyone else (only CLI bypasses) | Allowed through; admin bar indicator shown |
| **Auto-expiry** | Yes — 10 minutes, even without explicit deactivation | No — stays active until manually disabled |
| **WP root writable?** | Not required — uses `wp-content/` only | Not applicable (uses WP option) |
| **Custom page** | Default `wp_die()` page (or `enable_maintenance_mode` filter) | Branded HTML page (`assets/maintenance.html`) |
| **WP-CLI support** | Full (`activate`, `deactivate`, `status`, `is-active`) | None |

---

## WP-CLI commands

These commands override the built-in `wp maintenance-mode` command.

### `wp maintenance-mode activate`

Activates maintenance mode. If already active, resets the expiry timer.

```
$ wp maintenance-mode activate
Success: Maintenance mode activated.
```

### `wp maintenance-mode deactivate`

Deactivates maintenance mode. Removes the symlink and sentinel file.

```
$ wp maintenance-mode deactivate
Success: Maintenance mode deactivated.
```

### `wp maintenance-mode status`

Displays the current status including how long ago activation occurred and
whether the mode has expired.

```
$ wp maintenance-mode status
Success: Maintenance mode is active (activated 42 seconds ago).

$ wp maintenance-mode status
Maintenance mode is inactive.

$ wp maintenance-mode status
Warning: Maintenance mode is expired (activated 720 seconds ago, limit is 600 seconds).
```

### `wp maintenance-mode is-active`

Exits with code `0` if maintenance mode is currently active, `1` otherwise.
Designed for shell conditionals.

```bash
# In a deployment script:
wp maintenance-mode activate
run_update_process
wp maintenance-mode deactivate

# Check in a shell conditional:
wp maintenance-mode is-active && echo "site is in maintenance"
```
