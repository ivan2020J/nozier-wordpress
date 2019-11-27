<?php
declare(strict_types = 1);

/**
 * Plugin Name: Nozier
 * Plugin URI: https://nozier.com/
 * Description: Nozier monitors your website.
 * Version: 1.0.0
 * Author: Nozier
 * Author URI: https://nozier.com/
 */

if (!defined('ABSPATH')) {
    exit;
}

class NozierWordpressPlugin
{
    /**
     * Init the plugin.
     * @access public
     * @return void
     */
    public function init()
    {
        // Init the Nozier system.
        require __DIR__ . '/vendor/autoload.php';

        add_action('rest_api_init', function () {
            \Nozier\Wordpress\Routes::register();
        });
        add_action('admin_init', [$this, 'settings']);

        // Generate a fresh token when none is available.
        if (empty(get_option('nozier_token'))) {
            update_option('nozier_token', sha1(rand() . time()));
        }
    }

    /**
     * Provide the settings.
     * @access public
     * @return void
     */
    public function settings()
    {
        // Add the Nozier section to the General settings.
        add_settings_section(
            'nozier-settings',
            'Nozier Settings',
            function () {
                // Add the settings field.
                add_settings_field(
                    'nozier_token',
                    'Token',
                    function () {
                        // Output the settings field.
                        echo '<input
                            type="text"
                            name="nozier_token"
                            id="nozier_token"
                            value="' . get_option('nozier_token') . '" />';
                    },
                    'general',
                    'nozier-settings'
                );
            },
            'general',
        );

        register_setting('general', 'nozier_token');
    }
}
(new NozierWordpressPlugin)->init();
