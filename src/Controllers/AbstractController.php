<?php
declare(strict_types = 1);

namespace Nozier\Wordpress\Controllers;

use Nozier\Wordpress\Contracts\ControllerContract;

/**
 * Abstract controller is the base class for all Nozier controllers.
 */
abstract class AbstractController implements ControllerContract
{
    /**
     * Register the controller routes.
     *
     * @return void
     */
    abstract public function register(): void;
}
