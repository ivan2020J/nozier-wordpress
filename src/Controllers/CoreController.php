<?php
declare(strict_types = 1);

namespace Nozier\Wordpress\Controllers;

use Nozier\Wordpress\UpdaterSkin;
use Nozier\Client\Responses\FetchResponse;
use Nozier\Wordpress\Helpers\PluginHelper;
use Nozier\Client\Responses\UpdateCoreResponse;

/**
 * The core controller handles fetches, versions and updates.
 */
class CoreController extends AbstractController
{
    /**
     * Register the controller routes.
     * @access public
     * @return void
     */
    public function register(): void
    {
        // Fetch versions and updates.
        register_rest_route(
            'nozier/v1',
            '/core/fetch',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [$this, 'fetch'],
                    'permission_callback' => ['Nozier\Wordpress\Routes', 'verify'],
                ],
            ]
        );

        // Core update.
        register_rest_route(
            'nozier/v1',
            '/core/upgrade',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'upgradeCore'],
                    'permission_callback' => ['Nozier\Wordpress\Routes', 'verify'],
                ],
            ]
        );
    }

    /**
     * Fetch the software versions and updates.
     * @access public
     * @return void
     */
    public function fetch()
    {
        global $wpdb;

        $updates = PluginHelper::getUpdates();

        (new FetchResponse(
            PHP_VERSION,
            $wpdb->db_version(),
            (string) get_bloginfo('version'),
            $updates
        ))->setToken(get_option('nozier_token'))->send(); // @todo Change token.
        exit;
    }

    /**
     * Upgrade the core.
     * @access public
     * @return \Nozier\Client\Responses\UpdateCoreResponse|void
     */
    public function upgradeCore()
    {
        // Check if it is allowed to modify any files.
        if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
            return (new UpdateCoreResponse(false))
                ->setToken(get_option('nozier_token'))
                ->send();
        }

        // Include the required files for the updater.
        include_once ABSPATH . 'wp-admin/includes/admin.php';
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        include_once ABSPATH . 'wp-includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        // Check if the filesystem is writable.
        if (!$this->checkFilesystemWritable()) {
            return (new UpdateCoreResponse(false))
                ->setToken(get_option('nozier_token'))
                ->send();
        }

        // Force a refresh of the update.
        wp_version_check();
        $updates = get_core_updates();

        // Check if the updates could be retrieved.
        if (is_wp_error($updates) || !$updates) {
            return (new UpdateCoreResponse(false))
                ->setToken(get_option('nozier_token'))
                ->send();
        }

        // Reset the update array.
        $update = reset($updates);
        if (!$update) {
            return (new UpdateCoreResponse(false))
                ->setToken(get_option('nozier_token'))
                ->send();
        }

        $newVersion = $update->version;

        // Build the upgrader and execute the upgrade.
        $skin = new UpdaterSkin();
        $upgrader = new \Core_Upgrader($skin);
        $result = $upgrader->upgrade($update);

        if (is_wp_error($result)) {
            return (new UpdateCoreResponse(false))
                ->setToken(get_option('nozier_token'))
                ->send();
        }

        // Finish the upgrade.
        global $wp_current_db_version, $wp_db_version;
        require ABSPATH . WPINC . '/version.php';

        wp_upgrade();

        (new UpdateCoreResponse(true, $newVersion))
            ->setToken(get_option('nozier_token'))
            ->send();
    }

    /**
     * Check whether the filesystem is writeable.
     * @access protected
     * @return boolean
     */
    protected function checkFilesystemWritable(): bool
    {
        ob_start();
        $success = request_filesystem_credentials('');
        ob_end_clean();

        return (bool) $success;
    }
}
