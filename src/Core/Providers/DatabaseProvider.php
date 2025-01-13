<?php

namespace Nimbly\Foundation\Core\Providers;

use Nimbly\Carton\Container;
use UnexpectedValueException;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Nimbly\Carton\ServiceProviderInterface;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Config\SQLServerDriverConfig;
use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\MySQL\TcpConnectionConfig as MysqlTcpConnectionConfig;
use Cycle\Database\Config\Postgres\TcpConnectionConfig as PgsqlTcpConnectionConfig;
use Cycle\Database\Config\SQLServer\TcpConnectionConfig as SqlserverTcpConnectionConfig;

class DatabaseProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			DatabaseManager::class,
			function(Container $container): DatabaseManager {

				$config = [
					"default" => \config("database.default") ?: "default",
				];

				foreach( \config("database.connections") as $name => $connection ){

					$config["databases"][$name] = ["connection" => $name];
					$config["connections"][$name] = match( \config("database.connections.{$name}.adapter") ){
						"memory" => new SQLiteDriverConfig(
							connection: new MemoryConnectionConfig,
							queryCache: true,
						),

						"sqlite" => new SQLiteDriverConfig(
							connection: new FileConnectionConfig(
								database: \config("database.connections.{$name}.database")
							),
						),

						"mysql" => new MySQLDriverConfig(
							connection: new MysqlTcpConnectionConfig(
								host: \config("database.connections.{$name}.host"),
								port: \config("database.connections.{$name}.port") ?? 3306,
								database: \config("database.connections.{$name}.adapter"),
								user: \config("database.connections.{$name}.user"),
								password: \config("database.connections.{$name}.password"),
							),
						),

						"pgsql" => new PostgresDriverConfig(
							connection: new PgsqlTcpConnectionConfig(
								host: \config("database.connections.{$name}.host"),
								port: \config("database.connections.{$name}.port") ?? 3306,
								database: \config("database.connections.{$name}.adapter"),
								user: \config("database.connections.{$name}.username"),
								password: \config("database.connections.{$name}.password"),
							)
						),

						"sqlserver" => new SQLServerDriverConfig(
							connection: new SqlserverTcpConnectionConfig(
								host: \config("database.connections.{$name}.host"),
								port: \config("database.connections.{$name}.port") ?? 3306,
								database: \config("database.connections.{$name}.adapter"),
								user: \config("database.connections.{$name}.username"),
								password: \config("database.connections.{$name}.password"),
							)
						),

						default => throw new UnexpectedValueException("Unknown DB adapter.")
					};
				}

				return new DatabaseManager(
					new DatabaseConfig($config)
				);
			}
		);
	}
}