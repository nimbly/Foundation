<?php

use Nimbly\Caboodle\Config;
use Nimbly\Carton\Container;

if( !\function_exists("config") ){

	/**
	 * Get a configuration value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function config(string $key): mixed
	{
		return Container::getInstance()->get(Config::class)->get($key);
	}
}

if( !\function_exists("env") ){

	/**
	 * Get an environment variable.
	 *
	 * @param string $key Environment variable name.
	 * @param mixed $default Default value if envrionment variable not defined or is not set to anything (i.e. an empty string.)
	 * @param string|null $type The data type of the value. Options: "int", "bool".
	 * @return mixed
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