<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\Enums\HttpCodes;
use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class RestServiceErrorHandlingTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-error-tests-' . uniqid('', true);
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

    public function testNormalizeExceptionFallsBackToInternalServerErrorForInvalidCode(): void
    {
        $service = $this->createService();
        $normalized = $this->invokePrivateMethod(
            $service,
            'normalizeException',
            [new RuntimeException(message: '', code: 0)]
        );

        self::assertSame(HttpCodes::InternalServerError->value, $normalized->getCode());
        self::assertSame('Internal Server Error', $normalized->getMessage());
    }

    public function testNormalizeExceptionKeepsKnownHttpCodeAndMessage(): void
    {
        $service = $this->createService();
        $normalized = $this->invokePrivateMethod(
            $service,
            'normalizeException',
            [new RuntimeException(message: 'Forbidden', code: HttpCodes::Forbidden->value)]
        );

        self::assertSame(HttpCodes::Forbidden->value, $normalized->getCode());
        self::assertSame('Forbidden', $normalized->getMessage());
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
