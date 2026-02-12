# APCu Object Cache for WordPress

This directory contains the APCu-based object cache implementation for WordPress. It provides persistent in-memory caching using the APCu PHP extension, significantly improving WordPress performance by reducing database queries.

## Overview

The APCu object cache replaces WordPress's default transient object cache with a persistent in-memory cache. When enabled, it copies `object-cache.php` as a drop-in to `WP_CONTENT_DIR`, allowing WordPress to cache database query results, options, and other data in memory.

## Requirements

- PHP APCu extension must be installed and enabled
- WordPress 6.6+
- PHP 8.3+

## Enabling and Disabling

### Enable APCu Object Cache

To enable the APCu object cache, set the option to `1`:

```bash
pnpm wp-env run cli wp --quiet option update IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION '1'
```

This command:

1. Sets the enable flag in WordPress options
2. Copies `object-cache.php` to `WP_CONTENT_DIR/object-cache.php`
3. Activates persistent caching for all WordPress cache operations

_On `wp-env` the cache will be activated on next request due to `wp-env` architecture._

### Disable APCu Object Cache

To disable the APCu object cache, delete the option:

```bash
pnpm wp-env run cli wp --quiet option delete IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION
```

This command:

1. Removes the enable flag
2. Deletes the `object-cache.php` drop-in file
3. Flushes the APCu cache
4. WordPress reverts to its default non-persistent object cache

_On `wp-env` the cache will be activated on next request due to `wp-env` architecture._

### Check Status

To check if APCu object cache is enabled:

```bash
pnpm wp-env run cli wp --quiet option get IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION
```

To verify the drop-in file is active:

```bash
pnpm wp-env run wordpress ls -la wp-content/object-cache.php
```

## Testing and Performance Benchmarking

### Performance Measurement

To measure the performance impact of the APCu object cache, you can use WordPress's Server-Timing API to benchmark PHP performance. For comprehensive benchmarking guidance, refer to the official WordPress Performance Team documentation:

**[Benchmarking PHP Performance with Server-Timing](https://make.wordpress.org/performance/handbook/measuring-performance/benchmarking-php-performance-with-server-timing/#preparing-a-wordpress-site-for-server-timing-benchmarks)**

### Quick Performance Test

WordPress setup :

Follow these steps to prepare a WordPress site to perform a Server-Timing benchmark using the Performance Lab plugin:

- Install and activate the [Performance Lab plugin](https://wordpress.org/plugins/performance-lab/).
- Go to the _Settings > Performance_ screen, uncheck all module checkboxes and save. This ensures none of the Performance Lab’s other features are loaded, which could affect the performance of your actual comparison.
- Enable output-buffering for Server Timing via WP Admin > Tools > Server Timing and check the “Enable output buffering of template rendering” checkbox.
- (Optional, but recommended) Disable any debugging features of WordPress, e.g. set constants like `WP_DEBUG`, `SCRIPT_DEBUG`, `SAVEQUERIES`, to false.

Here's a simple approach to compare performance with and without APCu:

1. **Baseline (without APCu)**:

   ```bash
   # Disable APCu
   pnpm wp-env run cli wp --quiet option delete IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION

   # Clear all caches
   pnpm wp-env run wordpress php -r 'apcu_clear_cache();'

   # Load your site and measure response times
   ```

2. **With APCu enabled**:

   ```bash
   # Enable APCu
   pnpm wp-env run cli wp --quiet option update IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION '1'

   # Clear APCu cache for fresh start
   pnpm wp-env run wordpress php -r 'apcu_clear_cache();'

   # Load your site and measure response times
   ```

3. **Load your site and measure response times**

Local wp performance installation :

- install the https://github.com/GoogleChromeLabs/wpp-research project

  ```
  git clone https://github.com/GoogleChromeLabs/wpp-research.git
  cd wwp-research
  npm install
  ```

- run tests for a site :

  Example :

  ```
  npm run research -- benchmark-server-timing -u http://localhost:8888 -n 100 -p
  ```

4. **Compare**:
   - Database query counts
   - Page load times
   - Server response times
   - Time to first byte (TTFB)

### APCu object cache settings within wordpress

There are some APCU related PHP snippets in packages/wp-plugin/ionos-wpdev-caddy/ionos-wpdev-caddy/notebooks/notebooks/apcu which can be executed directly using the wpdev caddy plugin (for statistics for example).

### Expected Performance Improvements

With APCu object cache enabled, you should observe:

- **Reduced database queries**: 30-70% fewer queries on typical pages
- **Faster response times**: 20-50% improvement in TTFB
- **Lower server load**: Reduced CPU and database load
- **Better scalability**: Improved performance under concurrent load

## Implementation Details

The `object-cache.php` file implements the WordPress Object Cache API using APCu as the backend. Key features:

- **Full WordPress compatibility**: Implements all required cache functions
- **Group support**: Handles cache groups and global groups
- **Persistent caching**: Data survives between requests
- **Automatic cleanup**: Respects cache expiration times
- **Fallback handling**: Gracefully handles APCu unavailability

## Troubleshooting

### Object Cache Not Working

3. **Verify APCu memory limit**: Ensure APCu has sufficient memory configured in `php.ini`:
   ```ini
   apc.enabled=1
   apc.shm_size=64M  ; Adjust based on your needs
   ```

### Cache Not Clearing

If cache persists after clearing:

```bash
# Force clear and disable
pnpm wp-env run cli wp --quiet eval "apcu_clear_cache();"
pnpm wp-env run cli wp option delete IONOS_APCU_OBJECT_CACHE_ENABLED_OPTION
pnpm wp-env run cli wp cache flush
```

## See Also

- [WordPress Object Cache API](https://developer.wordpress.org/reference/classes/wp_object_cache/)
- [APCu PHP Extension Documentation](https://www.php.net/manual/en/book.apcu.php)
- [WordPress Performance Best Practices](https://developer.wordpress.org/advanced-administration/performance/)
