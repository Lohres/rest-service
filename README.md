# lohres/rest-service
REST Service for lohres projects

## Docs
> ### Example
> ```php
> require_once "config/config.php";
> require_once "vendor/autoload.php";
> 
> $logger = LogHelper::getLogger(name: "rest-service", level: LOHRES_LOG_LEVEL);
> $authService = new AuthService();
> 
> $restService = new RestService(
>    cachePath: LOHRES_CACHE_PATH,
>    filePath: LOHRES_ENDPOINT_PATH,
>    namespace: LOHRES_ENDPOINT_NAMESPACE,
>    replace: LOHRES_ENDPOINT_REPLACE,
>    logger: $logger,
>    authService: $authService
> );
> $restService->init();
> ``` 
>
