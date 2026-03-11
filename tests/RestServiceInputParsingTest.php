<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RestServiceInputParsingTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-input-tests-' . uniqid('', true);
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

    public function testJsonContentTypeDetectionSupportsSuffixTypes(): void
    {
        $service = $this->createService();

        $normalized = $this->invokePrivateMethod($service, 'normalizeContentType', ['Application/Vnd.Api+Json; Charset=UTF-8']);
        self::assertSame('application/vnd.api+json', $normalized);
        self::assertTrue($this->invokePrivateMethod($service, 'isJsonContentType', [$normalized]));
        self::assertTrue($this->invokePrivateMethod($service, 'isJsonContentType', ['application/json']));
        self::assertFalse($this->invokePrivateMethod($service, 'isJsonContentType', ['text/plain']));
    }

    public function testDecodeJsonBodyReturnsArrayForValidPayload(): void
    {
        $service = $this->createService();

        $decoded = $this->invokePrivateMethod($service, 'decodeJsonBody', ['{"foo":"bar","n":1}']);

        self::assertSame(['foo' => 'bar', 'n' => 1], $decoded);
    }

    public function testDecodeJsonBodyReturnsEmptyArrayForEmptyOrInvalidPayload(): void
    {
        $service = $this->createService();

        self::assertSame([], $this->invokePrivateMethod($service, 'decodeJsonBody', ['']));
        self::assertSame([], $this->invokePrivateMethod($service, 'decodeJsonBody', ['   ']));
        self::assertSame([], $this->invokePrivateMethod($service, 'decodeJsonBody', ['{"foo":']));
        self::assertSame([], $this->invokePrivateMethod($service, 'decodeJsonBody', ['"scalar"']));
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
