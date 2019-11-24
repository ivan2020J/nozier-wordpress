<?php
declare(strict_types = 1);

namespace Nozier\Wordpress\Controllers;

use Nozier\Client\Request;
use Nozier\Client\Response;
use Nozier\Wordpress\Helpers\PluginHelper;
use Nozier\Client\Responses\UpdateSoftwareResponse;

/**
 * Handles plugin installations, updates and manages them.
 */
class PluginController extends AbstractController
{
    /**
     * Register the controller routes.
     * @access public
     * @return void
     */
    public function register(): void
    {
        register_rest_route(
            'nozier/v1',
            '/plugins/update',
            [
                [
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'update'],
                    'permission_callback' => ['Nozier\Wordpress\Routes', 'verify'],
                ],
            ]
        );
    }

    /**
     * Update a plugin.
     * @access public
     * @return \Nozier\NozierClient\Response
     */
    public function update()
    {
        // We cannot update any plugins if the file modification flag is enabled.
        if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
            return (new Response(null, Response::HTTP_METHOD_NOT_ALLOWED))
                ->setToken('testing'); // @todo Change token.
        }

        // Validate the input.
        $request = Request::createFromGlobals();
        $json = $request->getJson();

        if (!property_exists($json, 'update') || !is_array($json->update)) {
            return (new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY))
                ->setToken('testing'); // @todo Change token.
        }

        $response = (new UpdateSoftwareResponse)->setToken('testing'); // @todo Change token.

        foreach ($json->update as $path) {
            try {
                if (!PluginHelper::update($path)) {
                    $response->addFailed($path);
                } else {
                    $response->addSuccess($path);
                }
            } catch (\Exception $e) {
                $response->addFailed($path);
            }
        }

        $response->send();
        exit;
    }
}