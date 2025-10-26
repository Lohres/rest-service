<?php declare(strict_types=1);

namespace Lohres\RestService;

/**
 * Class AuthService
 * Template class for auth services
 * @package Lohres\RestService
 */
abstract class AuthService
{
    /**
     * @param string $token
     */
    abstract public function checkToken(string $token): void;
}
