<?php declare(strict_types=1);

namespace Lohres\RestService;

use FilesystemIterator;
use JsonException;
use Lohres\RestService\Attributes\Auth;
use Lohres\RestService\Attributes\ExcludeFromMap;
use Lohres\RestService\Attributes\Method;
use Lohres\RestService\Attributes\Url;
use Lohres\RestService\Enums\HttpCodes;
use Lohres\RestService\Enums\RequestMethods;
use Monolog\Logger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Throwable;

/**
 * Class RestService
 * @package Lohres\RestService
 */
class RestService
{
    public const string CACHE_PATH = "cachePath";
    public const string CACHE_ENABLED = "cacheEnabled";
    public const string CACHE_FORCE_REBUILD = "cacheForceRebuild";
    public const string FILE_PATH = "filePath";
    public const string NAMESPACE = "namespace";
    public const string REPLACE = "replace";
    public const string CORS_ALLOWED_ORIGINS = "corsAllowedOrigins";
    public const string CORS_ALLOW_METHODS = "corsAllowMethods";
    public const string CORS_ALLOW_HEADERS = "corsAllowHeaders";
    public const string CORS_ALLOW_CREDENTIALS = "corsAllowCredentials";
    public const string CORS_MAX_AGE = "corsMaxAge";
    private array $config {
        get {
            return $this->config;
        }
        set {
            $this->config = $value;
        }
    }
    private array $map {
        get {
            return $this->map;
        }
        set {
            $this->map = $value;
        }
    }
    private ?Logger $logger {
        get {
            return $this->logger;
        }
        set {
            $this->logger = $value;
        }
    }

    private ?AuthService $authService {
        get {
            return $this->authService;
        }
        set {
            $this->authService = $value;
        }
    }

    /**
     * @param array $config
     * @param Logger|null $logger
     * @param AuthService|null $authService
     */
    public function __construct(
        array $config,
        ?Logger $logger = null,
        ?AuthService $authService = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->authService = $authService;
        $this->checkConfig();
        if (
            !@mkdir(directory: $this->config[self::CACHE_PATH], recursive: true) &&
            !is_dir(filename: $this->config[self::CACHE_PATH])
        ) {
            throw new RuntimeException(message: sprintf(
                'Directory "%s" was not created', $this->config[self::CACHE_PATH]
            ));
        }
        if (!is_dir(filename: $this->config[self::FILE_PATH])) {
            throw new RuntimeException(message: sprintf(
                'Directory "%s" does not exist', $this->config[self::FILE_PATH]
            ));
        }
        try {
            $cacheFile = $this->getCacheFilePath();
            if (!$this->isCacheEnabled()) {
                $this->map = $this->generateMap(persistCache: false);
            } elseif ($this->isCacheForceRebuild()) {
                $this->map = $this->generateMap(persistCache: true);
            } else {
                $cachedMap = $this->readMapFromCache(cacheFile: $cacheFile);
                if ($cachedMap !== null) {
                    $this->map = $cachedMap;
                } else {
                    $this->map = $this->generateMap(persistCache: true);
                }
            }
            $this->logger?->debug(message: "RestService initialized");
        } catch (Throwable $exception) {
            $this->logger?->error(message: $exception->getMessage(), context: [$exception->getTrace()]);
            $this->handleException(exception: new RuntimeException(
                message: HttpCodes::toString(code: HttpCodes::InternalServerError->value),
                code: HttpCodes::InternalServerError->value
            ));
        }
    }

    /**
     * @return void
     */
    public function init():void
    {
        try {
            $this->logger?->debug(message: "call init");
            if (PHP_SAPI !== "cli") {
                $this->parseInput();
                $this->cors();
                if ($_SERVER["REQUEST_METHOD"] === RequestMethods::OPTIONS->value) {
                    exit(0);
                }
                $this->checkAuthNeeded();
                $this->callEndpoint();
            } else {
                $exception = new RuntimeException(
                    message: HttpCodes::toString(HttpCodes::Forbidden->value),
                    code: HttpCodes::Forbidden->value
                );
                $this->logger?->error(message: $exception->getMessage(), context: [$exception->getTrace()]);
                $this->handleException(exception: $exception);
            }
        } catch (Throwable $exception) {
            die("ERROR: " . $exception->getMessage());
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    private function checkConfig(): void
    {
        $this->logger?->debug(message: "check config");
        if (
            empty($this->config[self::CACHE_PATH]) ||
            empty($this->config[self::FILE_PATH]) ||
            empty($this->config[self::NAMESPACE])
        ) {
            $exception = new RuntimeException(
                message: "config for rest-service invalid!",
                code: HttpCodes::InternalServerError->value
            );
            $this->logger?->error(message: $exception->getMessage(), context: [$exception->getTrace()]);
            $this->handleException(exception: $exception);
        }
    }

    /**
     * @return void
     * @throws JsonException
     */
    private function parseInput(): void
    {
        $this->logger?->debug(message: "parse input");
        $_POST = match ($_SERVER["CONTENT_TYPE"] ?? "") {
            "application/json;charset=utf-8", "application/json" => json_decode(
                json: file_get_contents("php://input"),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            ),
            default => $_POST
        };
    }

    /**
     * @param Response $response
     * @return void
     * @throws JsonException
     */
    private function prepareResponse(Response $response): void
    {
        header(header: "Content-type:application/json;charset=utf-8");
        echo json_encode(value: $response, flags: JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    private function handleException(Throwable $exception): void
    {
        try {
            $this->logger?->debug(message: "handle exception", context: [$exception->getTrace()]);
            header(header: "HTTP/1.0 {$exception->getCode()} {$exception->getMessage()}");
            $content = [
                "message" => $exception->getMessage(),
                "code" => (string)$exception->getCode()
            ];
            $response = new Response();
            $response->setContent(content: $content);
            $this->prepareResponse(response: $response);
            exit(0);
        } catch (Throwable $exception) {
            die("ERROR: " . $exception->getMessage());
        }
    }

    /**
     * @param string $target
     * @return array|bool
     */
    private function parseTarget(string $target): array|bool
    {
        $this->logger?->debug(message: "parse target $target");
        if (!str_contains(haystack: $target, needle: "@")) {
            return false;
        }
        return explode(separator: "@", string: $target);
    }

    /**
     * @param string $method
     * @param string $url
     * @return array
     */
    private function parseUrl(string $method, string $url): array
    {
        $this->logger?->debug(message: "parse url $url");
        $path = preg_replace(pattern: '/\?.*$/', replacement: "", subject: $url);
        if (!empty($this->config[self::REPLACE])) {
            $path = str_replace(search: $this->config[self::REPLACE], replace: "", subject: $path);
        }
        $path = trim(string: $path, characters: "/");
        $target = $this->map[$method][$path] ?? "";
        $targetArr = $this->parseTarget(target: $target);
        if (is_bool(value: $targetArr) || count(value: $targetArr) > 2) {
            $exception = new RuntimeException(
                message: "invalid rest-service target!",
                code: HttpCodes::NotFound->value
            );
            $this->logger?->error(message: $exception->getMessage(), context: [$exception->getTrace()]);
            $this->handleException(exception: $exception);
        }
        return $targetArr;
    }

    /**
     * @param string $token
     * @return string
     */
    private function getToken(string $token): string
    {
        $this->logger?->debug(message: "get token from $token");
        if (!str_contains(haystack: $token, needle: "Bearer")) {
            throw new RuntimeException(
                message: HttpCodes::toString(HttpCodes::Forbidden->value),
                code: HttpCodes::Forbidden->value
            );
        }
        return str_replace(search: "Bearer ", replace: "", subject: $token);
    }

    /**
     * @return string
     */
    private function getAuthorizationHeader(): string
    {
        $this->logger?->debug(message: "get authorization header");
        if (!empty($_SERVER["Authorization"])) {
            return trim(string: $_SERVER["Authorization"]);
        }
        if (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
            return trim(string: $_SERVER["HTTP_AUTHORIZATION"]);
        }
        if (function_exists(function: "apache_request_headers")) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                keys: array_map(callback: "ucwords", array: array_keys(array: $requestHeaders)),
                values: array_values(array: $requestHeaders)
            );
            if (isset($requestHeaders["Authorization"])) {
                return trim(string: $requestHeaders["Authorization"]);
            }
        }
        return "";
    }

    /**
     * @return void
     */
    private function checkAuthNeeded(): void
    {
        try {
            $this->logger?->debug(message: "check auth needed");
            $targetArr = $this->parseUrl(method: $_SERVER["REQUEST_METHOD"], url: $_SERVER["REQUEST_URI"]);
            $class = $this->config[self::NAMESPACE] . $targetArr[1];
            if (class_exists(class: $class)) {
                $reflection = new ReflectionClass(objectOrClass: $class);
                $method = $reflection->getMethod(name: $targetArr[0]);
                if ($method->isPublic()) {
                    $attributes = $method->getAttributes(name: Auth::class);
                    foreach ($attributes as $attribute) {
                        if ($attribute->getName() === Auth::class && $attribute->getArguments()[0]) {
                            $token = $this->getToken(token: $this->getAuthorizationHeader());
                            $this->authService?->checkToken(token: $token);
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            $this->logger?->error(message: $exception->getMessage(), context: [$exception->getTrace()]);
            $this->handleException(exception: $exception);
        }
    }

    /**
     * @return void
     */
    private function callEndpoint(): void
    {
        try {
            $this->logger?->debug(message: "call endpoint");
            [$class, $method] = $this->resolveEndpointTarget(
                httpMethod: $_SERVER["REQUEST_METHOD"],
                requestUri: $_SERVER["REQUEST_URI"]
            );
            $response = $this->invokeEndpointMethod(class: $class, method: $method);
            $this->prepareResponse(response: $response);
            exit(0);
        } catch (Throwable $exception) {
            $this->logger?->error(message: $exception->getMessage(), context: [$exception->getTrace()]);
            $this->handleException(exception: $exception);
        }
    }

    /**
     * @param string $httpMethod
     * @param string $requestUri
     * @return array{0:string,1:string}
     */
    private function resolveEndpointTarget(string $httpMethod, string $requestUri): array
    {
        $targetArr = $this->parseUrl(method: $httpMethod, url: $requestUri);
        $class = $this->config[self::NAMESPACE] . $targetArr[1];
        $method = $targetArr[0];
        if (!class_exists(class: $class)) {
            throw new RuntimeException(
                message: "endpoint class not found: $class",
                code: HttpCodes::NotFound->value
            );
        }

        $reflection = new ReflectionClass(objectOrClass: $class);
        if (!$reflection->hasMethod(name: $method)) {
            throw new RuntimeException(
                message: "endpoint method not found: $class::$method",
                code: HttpCodes::NotFound->value
            );
        }

        $reflectionMethod = $reflection->getMethod(name: $method);
        if (!$reflectionMethod->isPublic() || !$reflectionMethod->isStatic()) {
            throw new RuntimeException(
                message: "endpoint method must be public static: $class::$method",
                code: HttpCodes::InternalServerError->value
            );
        }

        return [$class, $method];
    }

    /**
     * @param string $class
     * @param string $method
     * @return Response
     */
    private function invokeEndpointMethod(string $class, string $method): Response
    {
        $response = $class::$method();
        if (!$response instanceof Response) {
            throw new RuntimeException(
                message: "endpoint method must return Response: $class::$method",
                code: HttpCodes::InternalServerError->value
            );
        }

        return $response;
    }

    /**
     * @return array
     * @throws JsonException
     */
    private function generateMap(bool $persistCache = true): array
    {
        $this->logger?->debug(message: "generate map");
        $mapList = [];
        $rdi = new RecursiveDirectoryIterator(
            directory: $this->config[self::FILE_PATH],
            flags: FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
        );
        $iterator = new RecursiveIteratorIterator(iterator: $rdi);
        foreach ($iterator as $file) {
            assert(assertion: $file instanceof SplFileInfo);
            if ($file->isFile() && $file->getExtension() === "php") {
                $fileName = $file->getBasename(suffix: ".php");
                $class = $this->config[self::NAMESPACE] . $fileName;
                if (class_exists(class: $class)) {
                    $reflection = new ReflectionClass(objectOrClass: $class);
                    $methods = $reflection->getMethods(filter: ReflectionMethod::IS_PUBLIC);
                    foreach ($methods as $method) {
                        $this->logger?->debug(message: "found method: " . $method->getName());
                        $attributes = $method->getAttributes();
                        $attributesNames = array_map(static fn($attribute) => $attribute->getName(), $attributes);
                        if (in_array(needle: ExcludeFromMap::class, haystack: $attributesNames, strict: true)) {
                            $this->logger?->debug(message: "$method excluded from map");
                            continue;
                        }
                        $httpMethod = "";
                        $url = "";
                        foreach ($attributes as $attribute) {
                            if ($attribute->getName() === Method::class) {
                                $httpMethod = $attribute->getArguments()[0];
                            }
                            if ($attribute->getName() === Url::class) {
                                $url = strtolower(string: $fileName) . "/" . $attribute->getArguments()[0];
                            }
                        }
                        if ($httpMethod !== "" && $url !== "") {
                            $mapList[$httpMethod][$url] = $method->getName() . "@" . $fileName;
                        }
                    }
                }
            }
        }
        if (!empty($mapList) && $persistCache && $this->isCacheEnabled()) {
            file_put_contents(
                filename: $this->getCacheFilePath(),
                data: json_encode(value: [
                    "signature" => $this->buildEndpointSignature(),
                    "map" => $mapList
                ], flags: JSON_THROW_ON_ERROR)
            );
            $this->logger?->debug(message: "map saved in cache");
        }
        return $mapList;
    }

    /**
     * @return void
     */
    private function cors(): void
    {
        $this->logger?->debug(message: "set cors");
        $requestOrigin = $_SERVER["HTTP_ORIGIN"] ?? "";
        $allowedOrigins = $this->getCorsAllowedOrigins();
        if ($requestOrigin !== "" && in_array(needle: $requestOrigin, haystack: $allowedOrigins, strict: true)) {
            header(header: "Access-Control-Allow-Origin: " . $requestOrigin);
        }
        if (in_array(needle: "*", haystack: $allowedOrigins, strict: true)) {
            header(header: "Access-Control-Allow-Origin: *");
        }
        header(header: "Access-Control-Allow-Credentials: " . ($this->isCorsCredentialsAllowed() ? "true" : "false"));
        header(header: "Access-Control-Max-Age: " . $this->getCorsMaxAge());
        header(header: "Access-Control-Allow-Methods: " . implode(separator: ", ", array: $this->getCorsAllowMethods()));
        header(header: "Access-Control-Allow-Headers: " . implode(separator: ", ", array: $this->getCorsAllowHeaders()));
    }

    /**
     * @return string[]
     */
    private function getCorsAllowedOrigins(): array
    {
        $configuredOrigins = $this->config[self::CORS_ALLOWED_ORIGINS] ?? null;
        if (is_array($configuredOrigins) && !empty($configuredOrigins)) {
            return array_values(array: array_filter(
                array_map(callback: static fn($origin) => is_string($origin) ? trim(string: $origin) : "", array: $configuredOrigins),
                static fn(string $origin) => $origin !== ""
            ));
        }

        if (defined(constant_name: "LOHRES_ALLOWED_ORIGINS") && is_array(LOHRES_ALLOWED_ORIGINS) && !empty(LOHRES_ALLOWED_ORIGINS)) {
            return LOHRES_ALLOWED_ORIGINS;
        }

        return ["*"];
    }

    /**
     * @return string[]
     */
    private function getCorsAllowMethods(): array
    {
        $configuredMethods = $this->config[self::CORS_ALLOW_METHODS] ?? null;
        if (is_array($configuredMethods) && !empty($configuredMethods)) {
            return array_values(array: array_filter(
                array_map(
                    callback: static fn($method) => is_string($method) ? strtoupper(string: trim(string: $method)) : "",
                    array: $configuredMethods
                ),
                static fn(string $method) => $method !== ""
            ));
        }

        return [RequestMethods::POST->value, RequestMethods::OPTIONS->value];
    }

    /**
     * @return string[]
     */
    private function getCorsAllowHeaders(): array
    {
        $configuredHeaders = $this->config[self::CORS_ALLOW_HEADERS] ?? null;
        if (is_array($configuredHeaders) && !empty($configuredHeaders)) {
            return array_values(array: array_filter(
                array_map(
                    callback: static fn($header) => is_string($header) ? trim(string: $header) : "",
                    array: $configuredHeaders
                ),
                static fn(string $header) => $header !== ""
            ));
        }

        return ["*", "Authorization"];
    }

    /**
     * @return bool
     */
    private function isCorsCredentialsAllowed(): bool
    {
        return (bool)($this->config[self::CORS_ALLOW_CREDENTIALS] ?? true);
    }

    /**
     * @return int
     */
    private function getCorsMaxAge(): int
    {
        $maxAge = $this->config[self::CORS_MAX_AGE] ?? 86400;
        if (!is_int($maxAge) || $maxAge < 0) {
            return 86400;
        }

        return $maxAge;
    }

    /**
     * @return bool
     */
    private function isCacheEnabled(): bool
    {
        return (bool)($this->config[self::CACHE_ENABLED] ?? true);
    }

    /**
     * @return bool
     */
    private function isCacheForceRebuild(): bool
    {
        return (bool)($this->config[self::CACHE_FORCE_REBUILD] ?? false);
    }

    /**
     * @return string
     */
    private function getCacheFilePath(): string
    {
        return $this->config[self::CACHE_PATH] . DIRECTORY_SEPARATOR . "rest-service-map.cache";
    }

    /**
     * @param string $cacheFile
     * @return array|null
     * @throws JsonException
     */
    private function readMapFromCache(string $cacheFile): ?array
    {
        if (!file_exists(filename: $cacheFile)) {
            return null;
        }

        $decoded = json_decode(
            json: file_get_contents(filename: $cacheFile),
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );
        if (!is_array(value: $decoded) || !isset($decoded["map"], $decoded["signature"])) {
            return null;
        }
        if (!is_array(value: $decoded["map"]) || !is_string(value: $decoded["signature"])) {
            return null;
        }
        if ($decoded["signature"] !== $this->buildEndpointSignature()) {
            $this->logger?->debug(message: "cache signature mismatch, rebuilding map");
            return null;
        }

        return $decoded["map"];
    }

    /**
     * @return string
     */
    private function buildEndpointSignature(): string
    {
        $items = [];
        $rdi = new RecursiveDirectoryIterator(
            directory: $this->config[self::FILE_PATH],
            flags: FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
        );
        $iterator = new RecursiveIteratorIterator(iterator: $rdi);
        foreach ($iterator as $file) {
            assert(assertion: $file instanceof SplFileInfo);
            if ($file->isFile() && $file->getExtension() === "php") {
                $items[] = sprintf(
                    "%s|%d|%d",
                    $file->getPathname(),
                    $file->getMTime(),
                    $file->getSize()
                );
            }
        }
        sort(array: $items);

        return sha1(string: implode(separator: "\n", array: $items));
    }
}
