# lohres/rest-service
REST Service for lohres projects

## Docs
### Example
```php
<?php declare(strict_types=1);

require_once "config/config.php";
require_once "vendor/autoload.php";

$config = [
    RestService::CACHE_PATH => "PATH/TO/YOUR/CACHE/DIRECTORY",
    RestService::CACHE_ENABLED => true,
    RestService::CACHE_FORCE_REBUILD => false,
    RestService::FILE_PATH => "PATH/TO/YOUR/ENDPOINTS/DIRECTORY",
    RestService::NAMESPACE => "\\YOUR\\ENDPOINTS\\NAMESPACE\\",
    RestService::REPLACE => "your-replace-string", // /your-replace-string/myEndpoint/function  -> myEndpoint/function
    RestService::CORS_ALLOWED_ORIGINS => ["https://example.com", "https://app.example.com"],
    RestService::CORS_ALLOW_METHODS => ["GET", "POST", "OPTIONS"],
    RestService::CORS_ALLOW_HEADERS => ["Authorization", "Content-Type"],
    RestService::CORS_ALLOW_CREDENTIALS => true,
    RestService::CORS_MAX_AGE => 86400
];

$logger = null // monolog Logger;
$authService = null // new AuthService() -> implement checkToken() method;

try {
    $restService = new RestService(
        config: $config,
        logger: $logger,
        authService: $authService
    );
    $restService->init();
} catch (Throwable $exception) {
    die("ERROR: " . $exception->getMessage());
}
``` 

## Endpoint Pattern

Endpoints are mapped via PHP attributes on `public static` methods.

```php
<?php declare(strict_types=1);

namespace App\Endpoints;

use Lohres\RestService\Attributes\Auth;
use Lohres\RestService\Attributes\Method;
use Lohres\RestService\Attributes\Url;
use Lohres\RestService\Enums\RequestMethods;
use Lohres\RestService\Response;

final class User
{
    #[Method(RequestMethods::GET->value)]
    #[Url('profile')]
    #[Auth(true)]
    public static function profile(): Response
    {
        $response = new Response();
        $response->setSuccess(true);
        $response->setContent(['id' => 1, 'name' => 'Ada']);

        return $response;
    }
}
```

The route is built as `<lowercase-classname>/<url-attribute>`.
In this example it is `user/profile`.

## Contracts

### Response

Each endpoint method must return a `Response` object.

- Standard fields:
  - `success` (`bool`)
  - `content` (`mixed`)
  - optional `debug` (`string`)
- Error responses also follow the `Response` structure with `content.message` and `content.code`.

### AuthService

For protected endpoints (`#[Auth(true)]`), a custom auth service can be injected:

```php
<?php declare(strict_types=1);

namespace App\Security;

use Lohres\RestService\AuthService;
use RuntimeException;

final class JwtAuthService extends AuthService
{
    public function checkToken(string $token): void
    {
        if ($token === '') {
            throw new RuntimeException('Forbidden', 403);
        }
    }
}
```

`checkToken(string $token): void` must throw an exception for invalid tokens.

## Configuration Validation

`RestService` validates configuration values early during startup:

- Required fields as non-empty strings: `cachePath`, `filePath`, `namespace`
- Type checks for optional values (e.g. `cacheEnabled` as `bool`, `corsMaxAge` as `int`)
- `cachePath` must be readable and writable
- `filePath` must exist and be readable

## Example Call

Run the local PHP server with the provided bootstrap:

```bash
php -S 127.0.0.1:8080 index.php
```

Call the sample endpoint (without auth):

```bash
curl http://127.0.0.1:8080/ping/hello
```

Expected response:

```json
{
  "success": true,
  "content": {
    "message": "Hello from RestService example endpoint"
  }
}
```
