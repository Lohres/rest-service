<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\Enums\HttpCodes;
use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class RestServiceEndpointDispatchTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-dispatch-tests-' . uniqid('', true);
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

    public function testResolveEndpointTargetReturnsClassAndMethodForValidRoute(): void
    {
        $service = $this->createService();

        $target = $this->invokePrivateMethod($service, 'resolveEndpointTarget', ['GET', '/user/ping']);

        self::assertSame(['Tests\\Fixtures\\Endpoints\\User', 'ping'], $target);
    }

    public function testResolveEndpointTargetThrowsForNonStaticEndpointMethod(): void
    {
        $service = $this->createService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(HttpCodes::InternalServerError->value);
        $this->expectExceptionMessage('endpoint method must be public static');

        $this->invokePrivateMethod($service, 'resolveEndpointTarget', ['GET', '/broken/instance']);
    }

    public function testInvokeEndpointMethodThrowsForInvalidReturnType(): void
    {
        $service = $this->createService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(HttpCodes::InternalServerError->value);
        $this->expectExceptionMessage('endpoint method must return Response');

        $this->invokePrivateMethod(
            $service,
            'invokeEndpointMethod',
            ['Tests\\Fixtures\\Endpoints\\Broken', 'invalidReturn']
        );
    }

    public function testInvokeEndpointMethodReturnsResponseForValidEndpoint(): void
    {
        $service = $this->createService();

        $response = $this->invokePrivateMethod(
            $service,
            'invokeEndpointMethod',
            ['Tests\\Fixtures\\Endpoints\\User', 'ping']
        );

        self::assertTrue($response->getSuccess());
        self::assertSame('pong', $response->getContent()['message']);
    }

    private function createService(): RestService
    {
        return new RestService(
            config: [
                RestService::CACHE_PATH => $this->cachePath,
                RestService::FILE_PATH => dirname(__DIR__) . '/tests/Fixtures/Endpoints',
                RestService::NAMESPACE => 'Tests\\Fixtures\\Endpoints\\',
                RestService::REPLACE => '',
            ]
        );
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
