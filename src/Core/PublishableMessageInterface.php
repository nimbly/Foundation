<?php

namespace Nimbly\Foundation\Core;

use Nimbly\Syndicate\Message;

/**
 * Implement this interface on your events if you would like them
 * to be published. This also requires the `PublishableMessageSubscriber`
 * to be registered in `config/events.php`.
 */
interface PublishableMessageInterface
{
	/**
	 * Get the Message instance.
	 *
	 * @return Message
	 */
	public function getPublishableMessage(): Message;
}