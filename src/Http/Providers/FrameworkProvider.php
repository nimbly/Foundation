<?php

namespace Nimbly\Foundation\Http\Providers;

use Nimbly\Foundation\Http\ExceptionHandler;
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
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$container->singleton(
			Application::class,
			function(Container $container): Application {
				return new Application(
					router: $container->has(Router::class) ?
						$container->get(Router::class) :
						$this->getRouter(\config("http.routes") ?? []),
					middleware: \config("http.middleware") ?? [],
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
}