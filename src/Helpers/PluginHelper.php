<?php
declare(strict_types = 1);

namespace Nozier\Wordpress\Helpers;

use Nozier\Wordpress\UpdaterSkin;
use Nozier\Client\Objects\UpdateObject;

abstract class PluginHelper
{
    /**
     * Refresh all updates.
     * @access public
     * @return void
     * @static
     */
    public static function refresh()
    {
        require_once ABSPATH . '/wp-includes/update.php';
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
        require_once ABSPATH . '/wp-admin/includes/update.php';

        // Clean the update cache.
        \wp_clean_update_cache();

        // Check the core version.
        \wp_version_check([], true);

        // Check for updates.
        \wp_update_plugins();
        \wp_update_themes();
    }

    /**
     * Get the list of plugin updates.
     * @access public
     * @return array
     * @static
     */
    public static function getUpdates(): array
    {
        // Refresh all updates.
        self::refresh();

        // Retrieve the translation, theme and plugin updates.
        $plugins = \get_plugin_updates();
        $translations = \wp_get_translation_updates();
        $themes = \get_theme_updates();

        // Format the plugin updates.
        $updates = array_map(function ($identifier, $plugin) {
            return new UpdateObject(
                'plugin-' . $identifier,
                $plugin->Name,
                $plugin->Version,
                $plugin->update->new_version
            );
        }, array_keys($plugins), $plugins);

        // Format the translation updates.
        // $updates = array_merge($updates, array_map(function ($translation) {
        //     // @todo
        // }, $translations));

        // Format the theme updates.
        $updates = array_merge($updates, array_map(function ($identifier, $theme) {
            return new UpdateObject(
                'theme-' . $identifier,
                $theme->title,
                $theme->version,
                $theme->update['new_version']
            );
        }, array_keys($themes), $themes));

        return $updates;
    }

    /**
     * Update the software.
     * @param  string $path The path.
     * @access public
     * @return boolean
     * @throws \InvalidArgumentException Thrown on invalid path.
     * @throws \Exception Thrown when the update failed.
     * @static
     */
    public static function update(string $path): bool
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Empty path.');
        }

        // Plugin update.
        if (strpos('plugin-', $path) === 0) {
            return self::updatePlugin(str_replace('plugin-', '', $path));
        }

        // Theme update.
        if (strpos('theme-', $path) === 0) {
            return self::updateTheme(str_replace('theme-', '', $path));
        }

        // @todo Translation updates.

        return false;
    }

    /**
     * Update a plugin.
     * @param  string $path The plugin path.
     * @access public
     * @return boolean
     * @throws \InvalidArgumentException Thrown on invalid path.
     * @throws \Exception Thrown when the update failed.
     * @static
     */
    public static function updatePlugin(string $path): bool
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Empty path.');
        }

        // Make sure no output from Wordpress leaves the buffer.
        ob_start(function () {
            return '';
        });

        // Require required files.
        require_once ABSPATH . '/wp-includes/update.php';
        require_once ABSPATH . '/wp-admin/includes/admin.php';
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
        require_once ABSPATH . '/wp-admin/includes/upgrade.php';
        require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader-skin.php';
        require_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';
        require_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader-skin.php';

        // Determine the active state of the plugin.
        $isActive = is_plugin_active($path);
        $isActiveNetwork = is_plugin_active_for_network($path);

        // Refresh the updates.
        self::refresh();

        // Create the updater objects and upgrade the plugin.
        $skin = new UpdaterSkin;
        $upgrader = new \Plugin_Upgrader($skin);
        $result = $upgrader->upgrade($path);

        if (ob_get_level()) {
            ob_end_clean();
        }

        // Check the result.
        if (is_wp_error($result)) {
            throw new \Exception($result->get_error_message(), $result->get_error_code());
        }

        // Check if the result is malformed.
        if ($result === false || is_null($result)) {
            return false;
        }

        // Re-activate the plugin if it was active before updating.
        if ($isActive) {
            ob_start(function () {
                return '';
            });

            activate_plugin($path, '', $isActiveNetwork, true);

            if (ob_get_level()) {
                ob_end_clean();
            }
        }

        return true;
    }

    /**
     * Update a theme.
     * @param  string $path The theme path.
     * @access public
     * @return boolean
     * @throws \InvalidArgumentException Thrown on invalid path.
     * @throws \Exception Thrown on failed theme update.
     * @static
     */
    public static function updateTheme(string $path): bool
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Empty path.');
        }

        // Make sure no output from Wordpress leaves the buffer.
        ob_start(function () {
            return '';
        });

        // Require required files.
        require_once ABSPATH . '/wp-includes/update.php';
        require_once ABSPATH . '/wp-admin/includes/admin.php';
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
        require_once ABSPATH . '/wp-admin/includes/upgrade.php';
        require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader-skin.php';
        require_once ABSPATH . '/wp-admin/includes/class-theme-upgrader.php';
        require_once ABSPATH . '/wp-admin/includes/class-theme-upgrader-skin.php';

        $upgrader = new \Theme_Upgrader(new UpdaterSkin);
        $result = $upgrader->upgrade($path);

        if (ob_get_level()) {
            ob_end_clean();
        }

        // Check the result.
        if (is_wp_error($result)) {
            throw new \Exception($result->get_error_message(), $result->get_error_code());
        }

        // Check if the result is malformed.
        return is_bool($result) && $result;
    }
}
