<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RestServiceCorsConfigTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-cors-tests-' . uniqid('', true);
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
    }

    public function testCorsUsesConfiguredValues(): void
    {
        $service = $this->createService([
            RestService::CORS_ALLOWED_ORIGINS => ['https://api.example.com'],
            RestService::CORS_ALLOW_METHODS => ['get', 'post', ' options '],
            RestService::CORS_ALLOW_HEADERS => ['Authorization', 'X-Custom'],
            RestService::CORS_ALLOW_CREDENTIALS => false,
            RestService::CORS_MAX_AGE => 600,
        ]);

        self::assertSame(
            ['https://api.example.com'],
            $this->invokePrivateMethod($service, 'getCorsAllowedOrigins')
        );
        self::assertSame(
            ['GET', 'POST', 'OPTIONS'],
            $this->invokePrivateMethod($service, 'getCorsAllowMethods')
        );
        self::assertSame(
            ['Authorization', 'X-Custom'],
            $this->invokePrivateMethod($service, 'getCorsAllowHeaders')
        );
        self::assertFalse($this->invokePrivateMethod($service, 'isCorsCredentialsAllowed'));
        self::assertSame(600, $this->invokePrivateMethod($service, 'getCorsMaxAge'));
    }

    public function testCorsMaxAgeFallsBackToDefaultForInvalidValue(): void
    {
        $service = $this->createService([
            RestService::CORS_MAX_AGE => -1,
        ]);

        self::assertSame(86400, $this->invokePrivateMethod($service, 'getCorsMaxAge'));
    }

    private function createService(array $extraConfig): RestService
    {
        return new RestService(
            config: array_merge(
                [
                    RestService::CACHE_PATH => $this->cachePath,
                    RestService::FILE_PATH => dirname(__DIR__) . '/tests/Fixtures/Endpoints',
                    RestService::NAMESPACE => 'Tests\\Fixtures\\Endpoints\\',
                    RestService::REPLACE => '',
                ],
                $extraConfig
            )
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
