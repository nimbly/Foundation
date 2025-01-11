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