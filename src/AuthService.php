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
        JwtHelper::checkToken(token: $token);
    }
}
