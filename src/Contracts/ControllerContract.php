<?php
declare(strict_types = 1);

namespace Nozier\Wordpress\Contracts;

interface ControllerContract
{
    /**
     * Register the controller routes.
     * @access public
     * @return void
     */
    public function register(): void;
}
