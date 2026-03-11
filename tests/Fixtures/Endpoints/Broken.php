<?php declare(strict_types=1);

namespace Tests\Fixtures\Endpoints;

use Lohres\RestService\Attributes\Method;
use Lohres\RestService\Attributes\Url;
use Lohres\RestService\Enums\RequestMethods;
use Lohres\RestService\Response;

final class Broken
{
    #[Method(RequestMethods::GET->value)]
    #[Url('instance')]
    public function instanceMethod(): Response
    {
        return new Response();
    }

    #[Method(RequestMethods::GET->value)]
    #[Url('invalid-return')]
    public static function invalidReturn(): string
    {
        return 'not-a-response';
    }
}
