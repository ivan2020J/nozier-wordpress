<?php
declare(strict_types = 1);

namespace Nozier\Wordpress;

require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader-skin.php';

class UpdaterSkin extends \WP_Upgrader_Skin
{
    /**
     * Show the header.
     *
     * @return void
     */
    public function header()
    {
        //
    }

    /**
     * Show the footer.
     *
     * @return void
     */
    public function footer()
    {
        //
    }

    /**
     * Show an error.
     * @param  mixed $errors The errors.
     *
     * @return void
     */
    public function error($errors)
    {
        //
    }

    /**
     * Show feedback.
     * @param  mixed $string The feedback string.
     *
     * @return void
     */
    public function feedback($string)
    {
        //
    }
}
