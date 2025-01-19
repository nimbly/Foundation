<?php

namespace Nimbly\Foundation\Core\Providers;

use Doctrine\ORM\ORMSetup;
use Nimbly\Carton\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;
use Nimbly\Carton\ServiceProviderInterface;

class DatabaseProvider implements ServiceProviderInterface
{
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$container->singleton(
			EntityManager::class,
			function(Container $container): EntityManager {

				$config = ORMSetup::createAttributeMetadataConfiguration(
					paths: ["app/Core/Entities"],
					isDevMode: \config("app.debug"),
				);

				$connection = DriverManager::getConnection([
					"driver" => \config("database.adapter"),
					"path" => \config("database.database"),
				], $config);

				return new EntityManager($connection, $config);
			}
		);
	}
}