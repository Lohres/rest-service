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
     * @param array{
     *   cachePath: string,
     *   filePath: string,
     *   namespace: string,
     *   replace?: string,
     *   cacheEnabled?: bool,
     *   cacheForceRebuild?: bool,
     *   corsAllowedOrigins?: string[],
     *   corsAllowMethods?: string[],
     *   corsAllowHeaders?: string[],
     *   corsAllowCredentials?: bool,
     *   corsMaxAge?: int
     * } $config
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
        if (!is_readable(filename: $this->config[self::FILE_PATH])) {
            throw new RuntimeException(message: sprintf(
                'Directory "%s" is not readable', $this->config[self::FILE_PATH]
            ));
        }
        if (!is_readable(filename: $this->config[self::CACHE_PATH])) {
            throw new RuntimeException(message: sprintf(
                'Directory "%s" is not readable', $this->config[self::CACHE_PATH]
            ));
        }
        if (!is_writable(filename: $this->config[self::CACHE_PATH])) {
            throw new RuntimeException(message: sprintf(
                'Directory "%s" is not writable', $this->config[self::CACHE_PATH]
            ));
        }
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
    }

    /**
     * @return void
     */
    public function init():void
    {
        try {
            $this->logger?->debug(message: "call init");
            if (PHP_SAPI === "cli") {
                throw new RuntimeException(
                    message: HttpCodes::toString(HttpCodes::Forbidden->value),
                    code: HttpCodes::Forbidden->value
                );
            }

            $this->parseInput();
            $this->cors();
            if (($_SERVER["REQUEST_METHOD"] ?? "") === RequestMethods::OPTIONS->value) {
                return;
            }
            $this->checkAuthNeeded();
            $this->callEndpoint();
        } catch (Throwable $exception) {
            $this->logException(event: "rest-service request failed", exception: $exception);
            $this->handleException(exception: $exception);
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
            throw new RuntimeException(
                message: "config for rest-service invalid!",
                code: HttpCodes::InternalServerError->value
            );
        }

        $requiredStringKeys = [self::CACHE_PATH, self::FILE_PATH, self::NAMESPACE];
        foreach ($requiredStringKeys as $key) {
            if (!is_string(value: $this->config[$key]) || trim(string: $this->config[$key]) === "") {
                throw new RuntimeException(
                    message: "config value must be non-empty string: $key",
                    code: HttpCodes::InternalServerError->value
                );
            }
        }

        if (array_key_exists(self::REPLACE, $this->config) && !is_string(value: $this->config[self::REPLACE])) {
            throw new RuntimeException(
                message: "config value must be string: " . self::REPLACE,
                code: HttpCodes::InternalServerError->value
            );
        }

        if (array_key_exists(self::CACHE_ENABLED, $this->config) && !is_bool(value: $this->config[self::CACHE_ENABLED])) {
            throw new RuntimeException(
                message: "config value must be bool: " . self::CACHE_ENABLED,
                code: HttpCodes::InternalServerError->value
            );
        }
        if (array_key_exists(self::CACHE_FORCE_REBUILD, $this->config) && !is_bool(value: $this->config[self::CACHE_FORCE_REBUILD])) {
            throw new RuntimeException(
                message: "config value must be bool: " . self::CACHE_FORCE_REBUILD,
                code: HttpCodes::InternalServerError->value
            );
        }

        if (array_key_exists(self::CORS_ALLOWED_ORIGINS, $this->config) && !is_array(value: $this->config[self::CORS_ALLOWED_ORIGINS])) {
            throw new RuntimeException(
                message: "config value must be array: " . self::CORS_ALLOWED_ORIGINS,
                code: HttpCodes::InternalServerError->value
            );
        }
        if (array_key_exists(self::CORS_ALLOW_METHODS, $this->config) && !is_array(value: $this->config[self::CORS_ALLOW_METHODS])) {
            throw new RuntimeException(
                message: "config value must be array: " . self::CORS_ALLOW_METHODS,
                code: HttpCodes::InternalServerError->value
            );
        }
        if (array_key_exists(self::CORS_ALLOW_HEADERS, $this->config) && !is_array(value: $this->config[self::CORS_ALLOW_HEADERS])) {
            throw new RuntimeException(
                message: "config value must be array: " . self::CORS_ALLOW_HEADERS,
                code: HttpCodes::InternalServerError->value
            );
        }
        if (array_key_exists(self::CORS_ALLOW_CREDENTIALS, $this->config) && !is_bool(value: $this->config[self::CORS_ALLOW_CREDENTIALS])) {
            throw new RuntimeException(
                message: "config value must be bool: " . self::CORS_ALLOW_CREDENTIALS,
                code: HttpCodes::InternalServerError->value
            );
        }
        if (array_key_exists(self::CORS_MAX_AGE, $this->config) && !is_int(value: $this->config[self::CORS_MAX_AGE])) {
            throw new RuntimeException(
                message: "config value must be int: " . self::CORS_MAX_AGE,
                code: HttpCodes::InternalServerError->value
            );
        }
    }

    /**
     * @return void
     */
    private function parseInput(): void
    {
        $this->logger?->debug(message: "parse input");
        $contentType = $this->normalizeContentType(contentType: $_SERVER["CONTENT_TYPE"] ?? "");
        if (!$this->isJsonContentType(contentType: $contentType)) {
            return;
        }

        $rawInput = file_get_contents(filename: "php://input");
        $_POST = $this->decodeJsonBody(rawInput: is_string($rawInput) ? $rawInput : "");
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
        $normalized = $this->normalizeException(exception: $exception);
        $this->logException(event: "prepare error response", exception: $normalized, level: "debug");
        header(header: "HTTP/1.0 {$normalized->getCode()} {$normalized->getMessage()}");
        $content = [
            "message" => $normalized->getMessage(),
            "code" => (string)$normalized->getCode()
        ];
        $response = new Response();
        $response->setContent(content: $content);

        try {
            $this->prepareResponse(response: $response);
        } catch (Throwable $exception) {
            $this->logException(event: "failed to prepare error response", exception: $exception);
            header(header: "Content-type:application/json;charset=utf-8");
            echo '{"success":false,"content":{"message":"Internal Server Error","code":"500"}}';
        }
    }

    /**
     * @param string $target
     * @return array|bool
     */
    private function parseTarget(string $target): array|bool
    {
        $this->logger?->debug(message: "parse target");
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
        $this->logger?->debug(message: "parse url");
        $path = preg_replace(pattern: '/\?.*$/', replacement: "", subject: $url);
        if (!empty($this->config[self::REPLACE])) {
            $path = str_replace(search: $this->config[self::REPLACE], replace: "", subject: $path);
        }
        $path = trim(string: $path, characters: "/");
        $target = $this->map[$method][$path] ?? "";
        $targetArr = $this->parseTarget(target: $target);
        if (is_bool(value: $targetArr) || count(value: $targetArr) > 2) {
            throw new RuntimeException(
                message: "invalid rest-service target!",
                code: HttpCodes::NotFound->value
            );
        }
        return $targetArr;
    }

    /**
     * @param string $token
     * @return string
     */
    private function getToken(string $token): string
    {
        $this->logger?->debug(
            message: "extract bearer token",
            context: ["token_preview" => $this->maskSensitiveValue(value: $token)]
        );
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
    }

    /**
     * @return void
     */
    private function callEndpoint(): void
    {
        $this->logger?->debug(message: "call endpoint");
        [$class, $method] = $this->resolveEndpointTarget(
            httpMethod: $_SERVER["REQUEST_METHOD"],
            requestUri: $_SERVER["REQUEST_URI"]
        );
        $response = $this->invokeEndpointMethod(class: $class, method: $method);
        $this->prepareResponse(response: $response);
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
     * @param string $contentType
     * @return string
     */
    private function normalizeContentType(string $contentType): string
    {
        return strtolower(string: trim(string: explode(separator: ";", string: $contentType)[0] ?? ""));
    }

    /**
     * @param string $contentType
     * @return bool
     */
    private function isJsonContentType(string $contentType): bool
    {
        return $contentType === "application/json" || str_ends_with(haystack: $contentType, needle: "+json");
    }

    /**
     * @param string $rawInput
     * @return array
     */
    private function decodeJsonBody(string $rawInput): array
    {
        $trimmed = trim(string: $rawInput);
        if ($trimmed === "") {
            return [];
        }

        try {
            $decoded = json_decode(json: $trimmed, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->logger?->warning(
                message: "invalid json payload",
                context: ["source" => "decodeJsonBody", "error" => $exception->getMessage()]
            );
            return [];
        }

        if (!is_array(value: $decoded)) {
            $this->logger?->warning(
                message: "json payload must decode to object or array",
                context: ["source" => "decodeJsonBody"]
            );
            return [];
        }

        return $decoded;
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

    /**
     * @param Throwable $exception
     * @return RuntimeException
     */
    private function normalizeException(Throwable $exception): RuntimeException
    {
        $code = (int)$exception->getCode();
        if (HttpCodes::tryFrom($code) === null) {
            $code = HttpCodes::InternalServerError->value;
        }

        $message = trim(string: $exception->getMessage());
        if ($message === "") {
            $message = HttpCodes::toString(code: $code);
        }

        return new RuntimeException(message: $message, code: $code, previous: $exception);
    }

    /**
     * @param string $event
     * @param Throwable $exception
     * @param string $level
     * @return void
     */
    private function logException(string $event, Throwable $exception, string $level = "error"): void
    {
        if ($this->logger === null) {
            return;
        }

        $context = [
            "event" => $event,
            "exception_class" => $exception::class,
            "code" => (int)$exception->getCode(),
            "message" => $exception->getMessage(),
        ];

        match ($level) {
            "debug" => $this->logger->debug(message: $event, context: $context),
            "warning" => $this->logger->warning(message: $event, context: $context),
            default => $this->logger->error(message: $event, context: $context),
        };
    }

    /**
     * @param string $value
     * @return string
     */
    private function maskSensitiveValue(string $value): string
    {
        $trimmed = trim(string: $value);
        if ($trimmed === "") {
            return "<empty>";
        }

        $length = strlen(string: $trimmed);
        if ($length <= 8) {
            return str_repeat(string: "*", times: $length);
        }

        $prefix = substr(string: $trimmed, offset: 0, length: 4);
        $suffix = substr(string: $trimmed, offset: -4);

        return $prefix . str_repeat(string: "*", times: $length - 8) . $suffix;
    }
}
