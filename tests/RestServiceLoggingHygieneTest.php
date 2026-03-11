<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RestServiceLoggingHygieneTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-logging-tests-' . uniqid('', true);
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

    public function testMaskSensitiveValueKeepsOnlyPrefixAndSuffix(): void
    {
        $service = $this->createService();

        self::assertSame(
            'Bear*********1234',
            $this->invokePrivateMethod($service, 'maskSensitiveValue', ['Bearer token-1234'])
        );
        self::assertSame('****', $this->invokePrivateMethod($service, 'maskSensitiveValue', ['abcd']));
        self::assertSame('<empty>', $this->invokePrivateMethod($service, 'maskSensitiveValue', ['   ']));
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
