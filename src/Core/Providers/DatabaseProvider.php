<?php

namespace Nimbly\Foundation\Core\Providers;

use Nimbly\Carton\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager;
use Nimbly\Carton\ServiceProviderInterface;

class DatabaseProvider implements ServiceProviderInterface
{
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$manager = new Manager;

		// Add each connection
		foreach( \config("database.connections") as $name => $options ){
			$manager->addConnection($options, $name);
		}

		// Set the event dispatcher.
		//$manager->setEventDispatcher(new Dispatcher);

		//Make this Capsule instance available globally.
		$manager->setAsGlobal();

		// Setup the Eloquent ORM.
		$manager->bootEloquent();

		$container->set(DB::class, $manager);
	}
}