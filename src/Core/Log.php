<?php

namespace Nimbly\Foundation\Core;

use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * This Log class is a simple static wrapper around the `Psr\Log\LoggerInterface`
 * class to be called where ever you need logging.
 *
 * @method static void debug(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void emergency(string $message, array $context = [])
 */
class Log
{
	private static ?LoggerInterface $logger = null;

	/**
	 * Initialize the logger with the Monolog instance.
	 *
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public static function init(LoggerInterface $logger): void
	{
		if( self::$logger === null ){
			self::$logger = $logger;
		}
	}

	/**
	 * Call instance method on Logger.
	 *
	 * @param string $method
	 * @param array<mixed> $params
	 * @throws NotFoundException
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $params)
	{
		if( self::$logger === null ){
			throw new UnexpectedValueException("Log class has not been initialized. Please call Log::init() method first.");
		}

		return \call_user_func_array(
			[self::$logger, $method],
			$params
		);
	}
}