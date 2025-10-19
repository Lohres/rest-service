<?php declare(strict_types=1);

namespace Lohres\RestService;

use JsonException;

/**
 * Class AuthService
 * Template class for auth services
 * @package Lohres\RestService
 */
class AuthService
{
    /**
     * @param string $token
     * @throws JsonException
     */
    public function checkToken(string $token): void
    {
        $payload = JwtHelper::checkToken(token: $token);
        define(constant_name: "CURRENT_USER_ID", value: (int)$payload["sub"]);
        define(constant_name: "CURRENT_USER_NAME", value: (int)$payload["name"]);
    }
}
