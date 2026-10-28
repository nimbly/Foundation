<?php

use Nimbly\Caboodle\Config;
use Nimbly\Carton\Container;

if( !\function_exists("config") ){

	/**
	 * Get a configuration value.
	 *
	 * @param string $key The configuration key to retrieve.
	 * @param mixed $default Default value if configuration key is not set.
	 * @return mixed The configuration value or the default if not set.
	 */
	function config(string $key, mixed $default = null): mixed
	{
		return Container::getInstance()->get(Config::class)->get($key) ?? $default;
	}
}

if( !\function_exists("env") ){

	/**
	 * Get an environment variable.
	 *
	 * @param string $key Environment variable name.
	 * @param mixed $default Default value if envrionment variable not defined or is not set to anything (i.e. an empty string.)
	 * @param string|null $type The data type of the value. Options: "int", "bool".
	 * @return mixed The environment variable value or the default if not set.
	 */
	function env(string $key, mixed $default = null, ?string $type = null): mixed
	{
		if( $key === "" ){
			throw new UnexpectedValueException("Environment key is empty.");
		}

		$value = \getenv($key);

		if( $value === false || $value === ""){
			$value = $default;
		}

		return match( $type ){
			"int" => (int) $value,
			"bool" => \in_array(\strtolower((string) $value), ["1", "true", "t", "yes", "y"]),
			default => $value
		};
	}
}