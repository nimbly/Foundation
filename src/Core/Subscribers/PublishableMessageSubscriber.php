<?php

namespace Nimbly\Foundation\Core\Subscribers;

use Nimbly\Announce\Subscribe;
use Nimbly\Syndicate\Adapter\PublisherInterface;
use Nimbly\Foundation\Core\PublishableMessageInterface;

/**
 * This subscriber listens to *all* locally triggered events that
 * implements the `PublishableMessageInterface` and publishes them
 * to an external broker.
 *
 * A common use case is that internally triggered events often have
 * value outside of the service itself and should be published to a
 * broker, a queue, or pubsub topic.
 *
 * @see `config/publisher.php`
 * @see DOCUMENTATION.md
 */
class PublishableMessageSubscriber
{
	public function __construct(
		protected PublisherInterface $publisher,
	)
	{
	}

	#[Subscribe(PublishableMessageInterface::class)]
	public function onEvent(PublishableMessageInterface $event): void
	{
		$this->publisher->publish($event->getPublishableMessage());
	}
}