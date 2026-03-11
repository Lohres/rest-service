<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RestServiceCacheStrategyTest extends TestCase
{
    private string $cachePath;
    private string $endpointsPath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-cache-tests-' . uniqid('', true);
        mkdir($this->cachePath, 0777, true);
        $this->endpointsPath = dirname(__DIR__) . '/tests/Fixtures/Endpoints';
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

    public function testCacheInvalidatesWhenEndpointFileChanges(): void
    {
        $service = $this->createService([]);
        $cacheFile = $this->cacheFile();
        self::assertFileExists($cacheFile);

        $firstPayload = json_decode((string) file_get_contents($cacheFile), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($firstPayload);
        self::assertIsString($firstPayload['signature'] ?? null);

        $endpointFile = $this->endpointsPath . DIRECTORY_SEPARATOR . 'User.php';
        $originalMTime = filemtime($endpointFile);
        self::assertNotFalse($originalMTime);

        try {
            touch($endpointFile, $originalMTime + 5);
            clearstatcache();

            $this->createService([]);

            $secondPayload = json_decode((string) file_get_contents($cacheFile), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($secondPayload);
            self::assertNotSame($firstPayload['signature'], $secondPayload['signature']);
        } finally {
            touch($endpointFile, $originalMTime);
            clearstatcache();
        }

        $map = $this->readPrivateProperty($service, 'map');
        self::assertSame('ping@User', $map['GET']['user/ping']);
    }

    public function testCacheForceRebuildIgnoresExistingCacheContent(): void
    {
        $bogusCache = [
            'signature' => 'fake-signature',
            'map' => ['GET' => ['user/ping' => 'broken@User']],
        ];
        file_put_contents(
            filename: $this->cacheFile(),
            data: json_encode($bogusCache, flags: JSON_THROW_ON_ERROR)
        );

        $service = $this->createService([
            RestService::CACHE_FORCE_REBUILD => true,
        ]);

        $map = $this->readPrivateProperty($service, 'map');
        self::assertSame('ping@User', $map['GET']['user/ping']);
    }

    public function testCacheCanBeDisabled(): void
    {
        $service = $this->createService([
            RestService::CACHE_ENABLED => false,
        ]);

        self::assertFileDoesNotExist($this->cacheFile());

        $map = $this->readPrivateProperty($service, 'map');
        self::assertSame('ping@User', $map['GET']['user/ping']);
    }

    private function createService(array $extraConfig): RestService
    {
        return new RestService(
            config: array_merge(
                [
                    RestService::CACHE_PATH => $this->cachePath,
                    RestService::FILE_PATH => $this->endpointsPath,
                    RestService::NAMESPACE => 'Tests\\Fixtures\\Endpoints\\',
                    RestService::REPLACE => '',
                ],
                $extraConfig
            )
        );
    }

    private function cacheFile(): string
    {
        return $this->cachePath . DIRECTORY_SEPARATOR . 'rest-service-map.cache';
    }

    private function readPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($object);
    }
}
