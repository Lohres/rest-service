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
