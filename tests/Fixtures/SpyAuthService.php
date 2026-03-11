<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Lohres\RestService\AuthService;

final class SpyAuthService extends AuthService
{
    /**
     * @var string[]
     */
    public array $checkedTokens = [];

    public function checkToken(string $token): void
    {
        $this->checkedTokens[] = $token;
    }
}
