<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Fixtures\SpyAuthService;

final class RestServiceAuthTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-auth-tests-' . uniqid('', true);
        mkdir($this->cachePath, 0777, true);
    }

    protected function tearDown(): void
    {
        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . 'rest-service-map.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        if (is_dir($this->cachePath)) {
            rmdir($this->cachePath);
        }

        unset(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['Authorization']
        );
    }

    public function testCheckAuthNeededUsesBearerTokenForProtectedEndpoint(): void
    {
        $authSpy = new SpyAuthService();
        $service = $this->createService($authSpy);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/user/secure';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-123';

        $this->invokePrivateMethod($service, 'checkAuthNeeded');

        self::assertSame(['token-123'], $authSpy->checkedTokens);
    }

    public function testCheckAuthNeededSkipsAuthForPublicEndpoint(): void
    {
        $authSpy = new SpyAuthService();
        $service = $this->createService($authSpy);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/ping';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer should-not-be-used';

        $this->invokePrivateMethod($service, 'checkAuthNeeded');

        self::assertSame([], $authSpy->checkedTokens);
    }

    private function createService(SpyAuthService $authSpy): RestService
    {
        return new RestService(
            config: [
                RestService::CACHE_PATH => $this->cachePath,
                RestService::FILE_PATH => dirname(__DIR__) . '/tests/Fixtures/Endpoints',
                RestService::NAMESPACE => 'Tests\\Fixtures\\Endpoints\\',
                RestService::REPLACE => '',
            ],
            authService: $authSpy
        );
    }

    private function invokePrivateMethod(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $privateMethod = $reflection->getMethod($method);
        $privateMethod->setAccessible(true);

        return $privateMethod->invokeArgs($object, $arguments);
    }
}
