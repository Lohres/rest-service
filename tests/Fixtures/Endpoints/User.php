<?php declare(strict_types=1);

namespace Tests\Fixtures\Endpoints;

use Lohres\RestService\Attributes\Auth;
use Lohres\RestService\Attributes\ExcludeFromMap;
use Lohres\RestService\Attributes\Method;
use Lohres\RestService\Attributes\Url;
use Lohres\RestService\Enums\RequestMethods;
use Lohres\RestService\Response;

final class User
{
    #[Method(RequestMethods::GET->value)]
    #[Url('ping')]
    #[Auth(false)]
    public static function ping(): Response
    {
        $response = new Response();
        $response->setSuccess(true);
        $response->setContent(['message' => 'pong']);

        return $response;
    }

    #[Method(RequestMethods::POST->value)]
    #[Url('secure')]
    #[Auth(true)]
    public static function secure(): Response
    {
        $response = new Response();
        $response->setSuccess(true);
        $response->setContent(['message' => 'secure']);

        return $response;
    }

    #[ExcludeFromMap]
    public static function hidden(): Response
    {
        return new Response();
    }
}
