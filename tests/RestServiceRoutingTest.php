<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RestServiceRoutingTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-tests-' . uniqid('', true);
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

    public function testGenerateMapContainsExpectedRoutes(): void
    {
        $service = $this->createService('/api');
        $map = $this->readPrivateProperty($service, 'map');

        self::assertSame('ping@User', $map['GET']['user/ping']);
        self::assertSame('secure@User', $map['POST']['user/secure']);
        self::assertArrayNotHasKey('user/hidden', $map['GET'] ?? []);
    }

    public function testParseUrlResolvesRouteWithReplacePrefixAndQueryString(): void
    {
        $service = $this->createService('/api');

        $result = $this->invokePrivateMethod(
            $service,
            'parseUrl',
            ['GET', '/api/user/ping?foo=bar']
        );

        self::assertSame(['ping', 'User'], $result);
    }

    private function createService(string $replace): RestService
    {
        return new RestService(
            config: [
                RestService::CACHE_PATH => $this->cachePath,
                RestService::FILE_PATH => dirname(__DIR__) . '/tests/Fixtures/Endpoints',
                RestService::NAMESPACE => 'Tests\\Fixtures\\Endpoints\\',
                RestService::REPLACE => $replace,
            ]
        );
    }

    private function readPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($object);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivateMethod(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $privateMethod = $reflection->getMethod($method);
        $privateMethod->setAccessible(true);

        return $privateMethod->invokeArgs($object, $arguments);
    }
}
