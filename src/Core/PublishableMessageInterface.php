<?php

namespace Nimbly\Foundation\Core;

use Nimbly\Syndicate\Message;

interface PublishableMessageInterface
{
	/**
	 * Get the Message instance.
	 *
	 * @return Message
	 */
	public function getPublishableMessage(): Message;
}