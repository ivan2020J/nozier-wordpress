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
    }
}
(new NozierWordpressPlugin)->init();
