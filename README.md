## Core
### Logger
Library: `monolog/monolog`

[ ] Configs:  config/logger.php
[ ] Provider: Core/Providers/LoggerProvider.php
[ ] Provider registered
[ ] Documentation

### Cache
Library: `symfony/cache`

[ ] Configs:  config/cache.php
[ ] Provider: Core/Providers/CacheProvider.php
[ ] Provider registered
[ ] Documentation

### Filesystem
Library: `league/flysystem`

[ ] Configs:  config/filesystem.php
[ ] Provider: Core/Providers/FilesystemProvider.php
[ ] Provider registered
[ ] Documentation

### Database
Library: `doctrine/orm`, `doctrine/dbal`, `ramsey/uuid-doctrine`

[ ] Configs
* config/database.php
* config/doctrine.php
[ ] Provider
* Core/Providers/DatabaseProvider.php
[ ] Provider registered
[ ] Documentation

### Migrations
Library: `nimbly/annouce`

[ ] Configs:
[ ] Provider:
[ ] Provider registered
[ ] Documentation

### Event dispatching
Library: `nimbly/annouce`

[ ] Configs:  config/event.php
[ ] Provider: Core/Providers/EventProvider.php
[ ] Provider registered
[ ] Documentation

### Publisher
Library: `nimbly/syndicate`

[ ] Configs:  config/publisher.php
[ ] Provider: Core/Providers/PublisherProvider.php
[ ] Provider registered
[ ] Documentation




## HTTP
### Framework
Library: `nimbly/limber`

[ ] Configs:  config/http.php
[ ] Provider: Http/Providers/FrameworkProvider.php
[ ] Provider registered
[ ] Documentation

### HTTP server
Library: `react/http`

[ ] Configs: config/http.php@server
[ ] Provider: Http/Providers/HttpServerProvider.php
[ ] Provider registered
[ ] Documentation

### JWT
Library: `nimbly/proof`

[ ] Configs: config/jwt.php
[ ] Provider: Http/Providers/JwtProvider.php
[ ] Provider registered
[ ] Documentation

### OpenAPI
Provide OpenAPI schema support out of the box with request and response validations.

Library: `league/openapi-psr7-validator`

[ ] Configs: config/http.php@schema
[ ] Provider: Http/Providers/SchemaValidatorProvider.php
[ ] Provider registered
[ ] Documentation


## Consumer

### Framework
Library: `nimbly/syndicate`

[ ] Configs:  config/consumer.php
[ ] Provider: Consumer/Providers/FrameworkProvider.php
[ ] Provider registered
[ ] Documentation

## Scheduler

### Framework
Library: `peppeocchi/php-cron-scheduler`

[ ] Configs:  config/scheduler.php
[ ] Provider: Scheduler/Providers/FrameworkProvider.php
[ ] Provider registered
[ ] Documentation