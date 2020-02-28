<?php
declare(strict_types = 1);

namespace Nozier\Wordpress;

use Nozier\Client\Request;
use Nozier\Client\Response;
use Nozier\Client\Exceptions\NoTokenException;
use Nozier\Client\Exceptions\SignatureException;
use Nozier\Client\Exceptions\TimestampException;
use Nozier\Wordpress\Controllers\CoreController;
use Nozier\Wordpress\Controllers\PluginController;

abstract class Routes
{
    /**
     * Register all the client routes.
     *
     * @return boolean
     */
    public static function register(): bool
    {
        $controllers = [
            new CoreController,
            new PluginController,
        ];

        foreach ($controllers as $controller) {
            $controller->register();
        }

        return true;
    }

    /**
     * Verify the incoming request.
     *
     * @return boolean
     */
    public static function verify(): bool
    {
        $request = Request::createFromGlobals();
        $token = get_option('nozier_token');

        try {
            $request->validate($token);
        } catch (NoTokenException $e) {
            http_response_code(Response::HTTP_UNPROCESSABLE_ENTITY);
            exit;
        } catch (TimestampException $e) {
            (new Response(null, Response::HTTP_EXPECTATION_FAILED))
                ->setToken($token)
                ->send();
            exit;
        } catch (SignatureException $e) {
            (new Response(null, Response::HTTP_UNAUTHORIZED))
                ->setToken($token)
                ->send();
            exit;
        } catch (\Throwable $e) {
            (new Response(null, Response::HTTP_SERVICE_UNAVAILABLE))
                ->setToken($token)
                ->send();
            exit;
        }

        return true;
    }
}
