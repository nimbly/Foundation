<?php

namespace Nimbly\Foundation\Http\Providers;

use React\EventLoop\Loop;
use React\Http\HttpServer;
use Nimbly\Carton\Container;
use Nimbly\Limber\Application;
use React\EventLoop\LoopInterface;
use Psr\Http\Message\ResponseInterface;
use Nimbly\Carton\ServiceProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nimbly\Capsule\Factory\ServerRequestFactory;
use Nimbly\Foundation\Core\Log;

/**
 * Provides the React/Http server instance necessary to run as a standalone
 * HTTP service.
 *
 * @see `config/http.php` for configuration options.
 */
class HttpServerProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			HttpServer::class,
			function(Container $container): HttpServer {

				/**
				 * Get the Application instance from the container.
				 *
				 * @var Application $application
				 */
				$application = $container->get(Application::class);

				return new HttpServer(
					$this->makeEventLoop(),
					new \React\Http\Middleware\StreamingRequestMiddleware,
					new \React\Http\Middleware\LimitConcurrentRequestsMiddleware(\config("http.server.max_connections") ?? 100),
					new \React\Http\Middleware\RequestBodyBufferMiddleware(\config("http.server.max_request_size") ?? 1048576),
					new \React\Http\Middleware\RequestBodyParserMiddleware,
					function(ServerRequestInterface $request) use ($application): ResponseInterface {
						return $application->dispatch(
							ServerRequestFactory::createServerRequestFromPsr7($request)
						);
					}
				);
			}
		);
	}

	/**
	 * Make the react/php event loop and attach signal handlers.
	 *
	 * @return LoopInterface
	 */
	private function makeEventLoop(): LoopInterface
	{
		$loop = Loop::get();

		foreach( \config("http.server.signals") ?? [] as $signal ){
			$loop->addSignal(
				$signal,
				function(int $signal) use ($loop): void {
					Log::info("Received interrupt signal.");
					$loop->stop();
					Log::info("HTTP server stopped.");
				}
			);
		}

		return $loop;
	}
}