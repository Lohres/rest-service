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
    /**
     * @var string
     */
    private string $cachePath {
        get {
            return $this->cachePath;
        }
        set {
            $this->cachePath = $value;
        }
    }

    /**
     * @var string
     */
    private string $filePath {
        get {
            return $this->filePath;
        }
        set {
            $this->filePath = $value;
        }
    }

    /**
     * @var array
     */
    private array $map {
        get {
            return $this->map;
        }
        set {
            $this->map = $value;
        }
    }

    /**
     * @var string
     */
    private string $namespace {
        get {
            return $this->namespace;
        }
        set {
            $this->namespace = $value;
        }
    }

    /**
     * @var string
     */
    private string $replace {
        get {
            return $this->replace;
        }
        set {
            $this->replace = $value;
        }
    }

    /**
     * @var Logger
     */
    private Logger $logger {
        get {
            return $this->logger;
        }
        set {
            $this->logger = $value;
        }
    }

    /**
     * @var AuthService
     */
    private AuthService $authService {
        get {
            return $this->authService;
        }
        set {
            $this->authService = $value;
        }
    }

    /**
     * @param string $cachePath
     * @param string $filePath
     * @param string $namespace
     * @param string $replace
     * @param Logger $logger
     * @param AuthService $authService
     */
    public function __construct(
        string $cachePath,
        string $filePath,
        string $namespace,
        string $replace,
        Logger $logger,
        AuthService $authService
    ) {
        if (empty($cachePath) || empty($filePath) || empty($namespace)) {
            $this->handleException(exception: new RuntimeException(
                message: "config for rest-service invalid!",
                code: HttpCodes::InternalServerError->value
            ));
        }
        $this->cachePath = $cachePath;
        $this->filePath = $filePath;
        $this->namespace = $namespace;
        $this->replace = $replace;
        $this->logger = $logger;
        $this->authService = $authService;
        if (!@mkdir(directory: $cachePath, recursive: true) && !is_dir(filename: $cachePath)) {
            throw new RuntimeException(message: sprintf('Directory "%s" was not created', $cachePath));
        }
        if (!is_dir(filename: $filePath)) {
            throw new RuntimeException(message: sprintf('Directory "%s" does not exist', $filePath));
        }
        try {
            $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . "api-map.cache";
            if (file_exists(filename: $cacheFile)) {
                $this->map = json_decode(
                    json: file_get_contents(filename: $cacheFile),
                    associative: true,
                    flags: JSON_THROW_ON_ERROR
                );
            } else {
                $this->map = $this->generateMap();
            }
        } catch (Throwable) {
            $this->handleException(new RuntimeException(
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
            if (PHP_SAPI !== "cli") {
                $this->parseInput();
                $this->cors();
                if ($_SERVER["REQUEST_METHOD"] === RequestMethods::OPTIONS->value) {
                    exit(0);
                }
                $this->checkAuthNeeded();
                $this->callEndpoint();
            } else {
                $this->handleException(exception: new RuntimeException(
                    message: HttpCodes::toString(HttpCodes::Forbidden->value),
                    code: HttpCodes::Forbidden->value
                ));
            }
        } catch (Throwable $exception) {
            die("ERROR: " . $exception->getMessage());
        }
    }

    /**
     * @return void
     * @throws JsonException
     */
    private function parseInput(): void
    {
        $_POST = match ($_SERVER["CONTENT_TYPE"]) {
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
            $this->logger->error(message: $exception->getMessage(), context: [$exception->getTrace()]);
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
        $path = preg_replace(pattern: '/\?.*$/', replacement: "", subject: $url);
        if ($this->replace !== "") {
            $path = str_replace(search: $this->replace, replace: "", subject: $path);
        }
        $path = trim(string: $path, characters: "/");
        $target = $this->map[$method][$path] ?? "";
        $targetArr = $this->parseTarget(target: $target);
        if (is_bool(value: $targetArr) || count(value: $targetArr) > 2) {
            $this->handleException(exception: new RuntimeException(
                message: "invalid rest-service target!",
                code: HttpCodes::NotFound->value
            ));
        }
        return $targetArr;
    }

    /**
     * @param string $token
     * @return string
     */
    private function getToken(string $token): string
    {
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
        if (!is_null(value: $_SERVER["Authorization"])) {
            return trim(string: $_SERVER["Authorization"]);
        }
        if (!is_null(value: $_SERVER["HTTP_AUTHORIZATION"])) {
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
            $targetArr = $this->parseUrl(method: $_SERVER["REQUEST_METHOD"], url: $_SERVER["REQUEST_URI"]);
            $class = $this->namespace . $targetArr[1];
            if (class_exists(class: $class)) {
                $reflection = new ReflectionClass(objectOrClass: $class);
                $method = $reflection->getMethod(name: $targetArr[0]);
                if ($method->isPublic()) {
                    $attributes = $method->getAttributes(name: Auth::class);
                    foreach ($attributes as $attribute) {
                        if ($attribute->getName() === Auth::class && $attribute->getArguments()[0]) {
                            $token = $this->getToken(token: $this->getAuthorizationHeader());
                            $this->authService->checkToken(token: $token);
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            $this->handleException(exception: $exception);
        }
    }

    /**
     * @return void
     */
    private function callEndpoint(): void
    {
        try {
            $targetArr = $this->parseUrl(method: $_SERVER["REQUEST_METHOD"], url: $_SERVER["REQUEST_URI"]);
            $class = $this->namespace . $targetArr[1];
            $method = $targetArr[0];
            $response = $class::$method();
            $this->prepareResponse(response: $response);
            exit(0);
        } catch (Throwable $exception) {
            $this->handleException(exception: $exception);
        }
    }

    /**
     * @return array
     * @throws JsonException
     */
    private function generateMap(): array
    {
        $mapList = [];
        $rdi = new RecursiveDirectoryIterator(
            directory: $this->filePath,
            flags: FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
        );
        $iterator = new RecursiveIteratorIterator(iterator: $rdi);
        foreach ($iterator as $file) {
            assert(assertion: $file instanceof SplFileInfo);
            if ($file->isFile() && $file->getExtension() === "php") {
                $fileName = $file->getBasename(suffix: ".php");
                $class = $this->namespace . $fileName;
                if (class_exists(class: $class)) {
                    $reflection = new ReflectionClass(objectOrClass: $class);
                    $methods = $reflection->getMethods(filter: ReflectionMethod::IS_PUBLIC);
                    foreach ($methods as $method) {
                        $attributes = $method->getAttributes();
                        $attributesNames = array_map(static fn($attribute) => $attribute->getName(), $attributes);
                        if (in_array(needle: ExcludeFromMap::class, haystack: $attributesNames, strict: true)) {
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
        if (!empty($mapList)) {
            file_put_contents(
                filename: $this->cachePath . DIRECTORY_SEPARATOR . "rest-service-map.cache",
                data: json_encode(value: $mapList, flags: JSON_THROW_ON_ERROR)
            );
        }
        return $mapList;
    }

    /**
     * @return void
     */
    private function cors(): void
    {
        if (in_array(needle: $_SERVER["HTTP_ORIGIN"], haystack: LOHRES_ALLOWED_ORIGINS, strict: true)) {
            header(header: "Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
        }
        if (LOHRES_ALLOWED_ORIGINS[0] === "*") {
            header(header: "Access-Control-Allow-Origin: *");
        }
        header(header: "Access-Control-Allow-Credentials: true");
        header(header: "Access-Control-Max-Age: 86400");    // cache for 1 day
        header(header: "Access-Control-Allow-Methods: POST, OPTIONS");
        header(header: "Access-Control-Allow-Headers: *, Authorization");
    }
}
