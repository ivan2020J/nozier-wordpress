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
     *
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
     *
     * @return \Nozier\NozierClient\Response
     */
    public function update()
    {
        // We cannot update any plugins if the file modification flag is enabled.
        if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
            return (new Response(null, Response::HTTP_METHOD_NOT_ALLOWED))
                ->setToken(get_option('nozier_token'))
                ->send();
        }

        // Validate the input.
        $request = Request::createFromGlobals();
        $json = $request->getJson();

        if (!property_exists($json, 'update') || !is_array($json->update)) {
            return (new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY))
                ->setToken(get_option('nozier_token'))
                ->send();
        }

        $response = (new UpdateSoftwareResponse)->setToken(get_option('nozier_token'));

        foreach ($json->update as $path) {
            try {
                PluginHelper::update($path)
                    ? $response->addSuccess($path)
                    : $response->addFailed($path);
            } catch (\Exception $e) {
                $response->addFailed($path);
            }
        }

        $response->send();
        exit;
    }
}
