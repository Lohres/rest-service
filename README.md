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
>     RestService::FILE_PATH => "PATH/TO/YOUR/ENDPOINTS/DIRECTORY",
>     RestService::NAMESPACE => "\\YOUR\\ENDPOINTS\\NAMESPACE\\",
>     RestService::REPLACE => "your-replace-string" // /your-replace-string/myEndpoint/function  -> myEndpoint/function
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
