<?php

defined('ABSPATH') || exit();

if (defined('WP_CLI') && WP_CLI) {

    /**
     * 1. THE PRE-EMPTIVE STRIKE
     * WordPress calls get_plugin_data() which triggers the file_get_contents warning.
     * By returning data here, we stop WP from ever trying to open the file.
     */
    add_filter('pre_get_plugin_data', function($data, $plugin_file) {
        if (strpos($plugin_file, '01-ext-ion8dhas7-stretch') !== false) {
            return [
                'Name'        => '01-ext-ion8dhas7-stretch',
                'Version'     => '1.0.0',
                'Description' => 'IONOS Stretch Asset (Virtual)',
                'Author'      => 'IONOS',
                'TextDomain'  => '01-ext-ion8dhas7-stretch',
            ];
        }
        return $data;
    }, 10, 2);

    /**
     * 2. THE PATH CORRECTOR
     * This forces WordPress to look at the real physical path instead of
     * the "ghost" path in public_html.
     */
    add_filter('plugin_file_path', function($path, $plugin) {
        if (strpos($path, '01-ext-ion8dhas7-stretch') !== false) {
            $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
            if (file_exists($helper_path)) {
                @require_once $helper_path;
                if (function_exists('\ionos\stretch_extra\secondary_plugin_dir\get_all_custom_plugins')) {
                    $all = \ionos\stretch_extra\secondary_plugin_dir\get_all_custom_plugins();
                    foreach ($all as $entry) {
                        if (strpos($entry['key'], $plugin) !== false) {
                            return $entry['file'];
                        }
                    }
                }
            }
        }
        return $path;
    }, 1, 2);

    /**
     * 3. THE LIST CLEANER
     * WP-CLI generates the "Warning" because 'all_plugins' contains keys that
     * don't exist on disk. We replace those keys with the REAL path.
     */
    add_filter('all_plugins', function($plugins) {
        $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
        if (!file_exists($helper_path)) return $plugins;

        @require_once $helper_path;
        if (!function_exists('\ionos\stretch_extra\secondary_plugin_dir\get_all_custom_plugins')) return $plugins;

        $mounted = \ionos\stretch_extra\secondary_plugin_dir\get_all_custom_plugins();
        foreach ($mounted as $entry) {
            $ghost_path = str_replace('plugins/', '', $entry['key']);
            $real_path  = $entry['file'];

            // If the ghost entry exists, remove it to stop the warning
            if (isset($plugins[$ghost_path])) {
                unset($plugins[$ghost_path]);
            }

            // Add the plugin using its REAL path as the key
            $plugins[$real_path] = [
                'Name'        => $entry['data']['Name'] ?? $ghost_path,
                'Version'     => $entry['version'] ?? '1.0.0',
                'Description' => 'IONOS Stretch Asset',
                'Author'      => 'IONOS',
                'Title'       => $entry['data']['Name'] ?? $ghost_path,
                'TextDomain'  => $ghost_path,
            ];
        }
        return $plugins;
    }, 999);

    /**
     * 4. COMMAND HIJACK (Activate/Deactivate)
     */
    WP_CLI::add_hook('before_invoke:plugin', function() {
        $runner = WP_CLI::get_runner();
        $subcommand = $runner->arguments[1] ?? '';
        $slug = $runner->arguments[2] ?? '';

        if (!in_array($subcommand, ['activate', 'deactivate'])) return;

        $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
        if (!file_exists($helper_path)) return;
        @require_once $helper_path;

        $all = \ionos\stretch_extra\secondary_plugin_dir\get_all_custom_plugins();
        foreach ($all as $entry) {
            $clean_key = str_replace('plugins/', '', $entry['key']);
            if ($entry['slug'] === $slug || $clean_key === $slug) {
                if ($subcommand === 'activate') {
                    \ionos\stretch_extra\secondary_plugin_dir\activate_custom_plugin($entry['key']);
                    WP_CLI::success("Activated: $slug");
                } else {
                    \ionos\stretch_extra\secondary_plugin_dir\deactivate_custom_plugin($entry['key']);
                    WP_CLI::success("Deactivated: $slug");
                }
                exit; // Exit early to prevent WP core from doing a file check
            }
        }
    });
}
