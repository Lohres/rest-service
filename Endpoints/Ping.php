<?php declare(strict_types=1);

namespace Endpoints;

use Lohres\RestService\Attributes\Auth;
use Lohres\RestService\Attributes\Method;
use Lohres\RestService\Attributes\Url;
use Lohres\RestService\Enums\RequestMethods;
use Lohres\RestService\Response;

final class Ping
{
    #[Method(RequestMethods::GET->value)]
    #[Url("hello")]
    #[Auth(false)]
    public static function hello(): Response
    {
        $response = new Response();
        $response->setSuccess(true);
        $response->setContent([
            "message" => "Hello from RestService example endpoint",
        ]);

        return $response;
    }
}
