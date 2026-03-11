<?php declare(strict_types=1);

use Lohres\RestService\RestService;

require_once __DIR__ . "/vendor/autoload.php";

define("LOHRES_ROOT", __DIR__);
const LOHRES_ALLOWED_ORIGINS = ["*"];

spl_autoload_register(static function (string $class): void {
    $prefix = "Endpoints\\";
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    if ($relativeClass === false || $relativeClass === "") {
        return;
    }

    $file = LOHRES_ROOT . "/Endpoints/" . str_replace("\\", "/", $relativeClass) . ".php";
    if (file_exists($file)) {
        require_once $file;
    }
});

$config = [
    RestService::CACHE_PATH => LOHRES_ROOT . "/cache",
    RestService::FILE_PATH => LOHRES_ROOT . "/Endpoints",
    RestService::NAMESPACE => "\\Endpoints\\",
    RestService::REPLACE => "",
    RestService::CORS_ALLOWED_ORIGINS => ["*"],
];

try {
    $restService = new RestService(config: $config);
    $restService->init();
} catch (Throwable $exception) {
    header("HTTP/1.0 500 Internal Server Error");
    header("Content-type:application/json;charset=utf-8");
    echo json_encode([
        "success" => false,
        "content" => [
            "message" => $exception->getMessage(),
            "code" => (string)$exception->getCode(),
        ],
    ], JSON_UNESCAPED_UNICODE);
}
