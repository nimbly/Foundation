<?php

namespace Nimbly\Foundation\Core\Attributes;

use Attribute;

/**
 * This attribute can be added to parameters to specify a configuration key and an optional default value
 * that will be used in autowiring.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Config
{
	/**
	 * @param string $key The config key.
	 * @param mixed $default The default value if the config key is not set or the value is null.
	 */
	public function __construct(
		protected string $key,
		protected mixed $default = null,
	){}

	/**
	 * Get the config key.
	 *
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Get the default value.
	 *
	 * @return mixed
	 */
	public function getDefault(): mixed
	{
		return $this->default;
	}
}