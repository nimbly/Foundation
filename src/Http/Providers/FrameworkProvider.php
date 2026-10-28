<?php

namespace Nimbly\Foundation\Http\Providers;

use Nimbly\Foundation\Http\ExceptionHandler;
use Nimbly\Limber\Exceptions\ApplicationException;
use Psr\Http\Server\MiddlewareInterface;
use Nimbly\Foundation\Core\Attributes\Config;
use Nimbly\Resolve\Resolve;
use Nimbly\Carton\Container;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Limber\Application;
use Nimbly\Limber\ExceptionHandlerInterface;
use Nimbly\Limber\Router\Router;

/**
 * Provides the Limber HTTP framework instance.
 *
 * @see `config/http.php` for configuration options.
 */
class FrameworkProvider implements ServiceProviderInterface
{
	use Resolve;

	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		if( !$container->has(Router::class) ){
			$container->set(
				Router::class,
				$this->getRouter(\config("http.routes") ?? [])
			);
		}

		$container->singleton(
			Application::class,
			function(Container $container): Application {
				return new Application(
					router: $container->get(Router::class),
					middleware: $this->normalizeMiddleware($container, \config("http.middleware") ?? []),
					container: $container,
					exceptionHandler: $container->has(ExceptionHandlerInterface::class) ?
						$container->get(ExceptionHandlerInterface::class) :
						new ExceptionHandler(
							default_message: \config("http.default_error_message") ?? "There was an issue processing your request.",
							debug: (bool) \config("app.debug") ?? false
						)
				);
			}
		);
	}

	/**
	 * Build the routes from the config.
	 *
	 * @param array<string> $routes List of route files to include.
	 * @return Router
	 */
	private function getRouter(array $routes): Router
	{
		$router = new Router;

		foreach( $routes as $route ){
			require_once $route;
		}

		return $router;
	}

	/**
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param Container $container
	 * @param mixed<string|MiddlewareInterface> $middlewares
	 * @return array<MiddlewareInterface>
	 */
	private function normalizeMiddleware(Container $container, array $middlewares): array
	{
		$normalized_middlewares = [];

		foreach( $middlewares as $middleware ){

			if( \is_string($middleware) ){
				$middleware = $this->make($middleware, $container, $this->resolveConfigAttributes($middleware));
			}

			if( empty($middleware) || $middleware instanceof MiddlewareInterface === false ){
				throw new ApplicationException("Provided middleware must be an instance of Psr\Http\Server\MiddlewareInterface or a class-string that references an Psr\Http\Server\MiddlewareInterface implementation.");
			}

			$normalized_middlewares[] = $middleware;
		}

		return $normalized_middlewares;
	}

	/**
	 * Resolve any config values that should be injected into the class constructor.
	 *
	 * @param string $class Class name to resolve config attributes for.
	 * @return array<string,mixed>
	 */
	private function resolveConfigAttributes(string $class): array
	{
		$reflection = new \ReflectionClass($class);
		$constructor = $reflection->getConstructor();

		if( !$constructor ){
			return [];
		}

		$configs = [];
		foreach( $constructor->getParameters() as $parameter ){
			$attributes = $parameter->getAttributes(Config::class);
			if( !empty($attributes) ){
				$config = $attributes[0]->newInstance();
				$configs[$parameter->getName()] = \config($config->getKey(), $config->getDefault());
			}
		}

		return $configs;
	}
}