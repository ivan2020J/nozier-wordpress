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

        // Force an update check.
        delete_site_transient('update_plugins');
        wp_version_check([], true);
        wp_update_plugins();
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

        // Retrieve the updates.
        $updates = \get_plugin_updates();
        return array_map(function ($identifier, $update) {
            return new UpdateObject(
                $identifier,
                $update->Name,
                $update->Version,
                $update->update->new_version
            );
        }, array_keys($updates), $updates);
    }

    /**
     * Update the given plugin.
     * @param  string $path The plugin path.
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
}
