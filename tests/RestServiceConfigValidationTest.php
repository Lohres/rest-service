<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\RestService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestServiceConfigValidationTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/rest-service-config-tests-' . uniqid('', true);
        mkdir($this->cachePath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cachePath)) {
            chmod($this->cachePath, 0777);
        }

        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . 'rest-service-map.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        if (is_dir($this->cachePath)) {
            rmdir($this->cachePath);
        }
    }

    public function testThrowsForInvalidConfigValueTypes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('config value must be bool: cacheEnabled');

        $this->createService([
            RestService::CACHE_ENABLED => 'yes',
        ]);
    }

    public function testThrowsForInvalidCorsType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('config value must be array: corsAllowMethods');

        $this->createService([
            RestService::CORS_ALLOW_METHODS => 'GET,POST',
        ]);
    }

    public function testThrowsWhenCachePathIsNotWritable(): void
    {
        chmod($this->cachePath, 0555);
        if (is_writable($this->cachePath)) {
            self::markTestSkipped('filesystem permissions do not enforce read-only mode in this environment');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not writable');

        $this->createService([]);
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
}
