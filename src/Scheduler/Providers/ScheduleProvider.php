<?php

namespace Nimbly\Foundation\Scheduler\Providers;

use Nimbly\Carton\Container;
use Nimbly\Carton\ServiceProviderInterface;
use GO\Scheduler;

class ScheduleProvider implements ServiceProviderInterface
{
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$container->singleton(
			Scheduler::class,
			function(Container $container): Scheduler {

				$scheduler = new Scheduler;

				foreach( \config("scheduler.schedules") ?? [] as $schedule => $handlers ){
					foreach( $handlers as $handler ){
						$scheduler->call(
							function() use ($container, $handler): mixed {
								return $container->call(
									$container->makeCallable($handler)
								);
							},
						)->at($schedule);
					}
				}

				return $scheduler;
			}
		);
	}
}