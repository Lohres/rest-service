# lohres/rest-service
REST Service for lohres projects

## Docs
> ### Example
> ```php
> require_once "config/config.php";
> require_once "vendor/autoload.php";
> 
> $config = [
>     RestService::CACHE_PATH => "PATH/TO/YOUR/CACHE/DIRECTORY",
>     RestService::CACHE_ENABLED => true,
>     RestService::CACHE_FORCE_REBUILD => false,
>     RestService::FILE_PATH => "PATH/TO/YOUR/ENDPOINTS/DIRECTORY",
>     RestService::NAMESPACE => "\\YOUR\\ENDPOINTS\\NAMESPACE\\",
>     RestService::REPLACE => "your-replace-string", // /your-replace-string/myEndpoint/function  -> myEndpoint/function
>     RestService::CORS_ALLOWED_ORIGINS => ["https://example.com", "https://app.example.com"],
>     RestService::CORS_ALLOW_METHODS => ["GET", "POST", "OPTIONS"],
>     RestService::CORS_ALLOW_HEADERS => ["Authorization", "Content-Type"],
>     RestService::CORS_ALLOW_CREDENTIALS => true,
>     RestService::CORS_MAX_AGE => 86400
> ];
> 
> $logger = null // monolog Logger;
> $authService = null // new AuthService() -> implement checkToken() method;
>
> try {
>     $restService = new RestService(
>         config: $config,
>         logger: $logger,
>         authService: $authService
>     );
>     $restService->init();
> } catch (Throwable $exception) {
>     die("ERROR: " . $exception->getMessage());
> }
> ``` 
>

## Endpoint Pattern

Endpoints werden ueber PHP-Attribute auf `public static` Methoden abgebildet.

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

Die Route wird dabei als `<klassenname-klein>/<url-attribute>` gebildet.
Im Beispiel ist das `user/profile`.

## Contracts

### Response

Jede Endpoint-Methode muss ein `Response` Objekt zurueckgeben.

- Standardfelder:
  - `success` (`bool`)
  - `content` (`mixed`)
  - optional `debug` (`string`)
- Fehlerantworten folgen ebenfalls der `Response`-Struktur mit `content.message` und `content.code`.

### AuthService

Fuer geschuetzte Endpoints (`#[Auth(true)]`) kann ein eigener Auth-Service injiziert werden:

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

`checkToken(string $token): void` muss bei ungueltigen Tokens eine Exception werfen.

## Konfigurationsvalidierung

`RestService` validiert Konfigurationswerte beim Start fruehzeitig:

- Pflichtfelder als nicht-leere Strings: `cachePath`, `filePath`, `namespace`
- Typpruefung optionaler Werte (z. B. `cacheEnabled` als `bool`, `corsMaxAge` als `int`)
- `cachePath` muss lesbar und schreibbar sein
- `filePath` muss existieren und lesbar sein
