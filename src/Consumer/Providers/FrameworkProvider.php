<?php

namespace Nimbly\Foundation\Consumer\Providers;

use IronMQ\IronMQ;
use Predis\Client as RedisClient;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Pheanstalk\Pheanstalk;
use Nimbly\Carton\Container;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use PhpMqtt\Client\MqttClient;
use Nimbly\Syndicate\Adapter\RabbitMQ;
use Nimbly\Syndicate\Adapter\Sqs;
use Nimbly\Syndicate\Adapter\Sns;
use Nimbly\Syndicate\Adapter\Iron;
use Nimbly\Syndicate\Application;
use Nimbly\Syndicate\Adapter\MockQueue;
use Nimbly\Syndicate\Adapter\Mqtt;
use Nimbly\Syndicate\Adapter\Azure;
use Nimbly\Syndicate\Adapter\Google;
use PhpAmqpLib\Channel\AMQPChannel;
use Google\Cloud\PubSub\PubSubClient;
use Nimbly\Syndicate\Adapter\Beanstalk;
use Nimbly\Syndicate\Router\RouterInterface;
use Nimbly\Syndicate\Adapter\ConsumerInterface;
use Nimbly\Syndicate\Adapter\PublisherInterface;
use PhpMqtt\Client\Contracts\Repository;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Syndicate\Adapter\Redis as RedisQueue;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use Nimbly\Syndicate\Adapter\Gearman;
use Nimbly\Syndicate\Adapter\Mercure;
use Nimbly\Syndicate\Adapter\NullPublisher;
use Nimbly\Syndicate\Adapter\Outbox;
use Nimbly\Syndicate\Adapter\RedisPubsub;
use Nimbly\Syndicate\Adapter\Segment;
use Nimbly\Syndicate\Adapter\Webhook;
use Nimbly\Syndicate\Filter\RedirectFilter;
use Nimbly\Syndicate\Router\Router;
use PDO;
use Psr\Http\Client\ClientInterface;
use Segment\Client as SegmentClient;

/**
 * Provides the Syndicate consumer framework instance.
 *
 * @see `config/consumer.php` for configuration options.
 */
class FrameworkProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			Application::class,
			function(Container $container): Application {

				$consumer = $container->has(ConsumerInterface::class) ?
					$container->get(ConsumerInterface::class) :
					self::resolveAdapter(\config("consumer.adapter"), $container);

				if( $consumer instanceof ConsumerInterface === false ){
					throw new UnexpectedValueException(
						\sprintf("Adapter \"%s\" is not a consumer.", $consumer::class)
					);
				}

				return new Application(
					consumer: $consumer,
					router: $container->has(RouterInterface::class) ?
						$container->get(RouterInterface::class) :
						new Router(
							handlers: \config("consumer.handlers") ?? [],
							default: \config("consumer.default_handler")
						),
					container: $container,
					signals: \config("consumer.signals") ?? [SIGHUP, SIGINT, SIGTERM],
					deadletter: self::resolveDeadletterPublisher(
							\config("consumer.deadletter.adapter"),
							\config("consumer.deadletter.topic"),
							$container
						),
					middleware: \config("consumer.middleware")
				);
			}
		);
	}

	/**
	 * Resolve the adapter into a consumer or publisher instance.
	 *
	 * @param string $adapter
	 * @param Container $container
	 * @return ConsumerInterface|PublisherInterface
	 */
	public static function resolveAdapter(string $adapter, Container $container): ConsumerInterface|PublisherInterface
	{
		$instance = match( $adapter ){
			"azure" => $container->has(Azure::class) ?
				$container->get(Azure::class) :
				new Azure(
					client: QueueRestProxy::createQueueService(
						\config("consumer.host")
					)
				),

			"beanstalkd" => $container->has(Beanstalk::class) ?
				$container->get(Beanstalk::class) :
				new Beanstalk(
					client: Pheanstalk::create(
						\config("consumer.host"),
						\config("consumer.port"),
					)
				),

			"gearman" => $container->has(Gearman::class) ?
				$container->get(Gearman::class) :
				new Gearman(
					client: \config("publisher.adapter") === "gearman" ?
						self::getGearmanInstance(new \GearmanClient, \config("publisher.host"), \config("publisher.gearman.options")) :
						null,
					worker: \config("consumer.adapter") === "gearman" ?
					self::getGearmanInstance(new \GearmanWorker, \config("consumer.host"), \config("consumer.gearman.options")) :
						null,
				),

			"google" => $container->has(Google::class) ?
				$container->get(Google::class) :
				new Google(
					client: new PubSubClient([
						"projectId" => \config("consumer.google.project_id"),
					])
				),

			"ironmq" => $container->has(Iron::class) ?
				$container->get(Iron::class) :
				new Iron(
					client: new IronMQ([
						"token" => \config("consumer.ironmq.token"),
						"project_id" => \config("consumer.ironmq.project_id"),
						"protocol" => \config("consumer.ironmq.protocol"),
						"host" => \config("consumer.host"),
						"port" => \config("consumer.port"),
						"api_version" => \config("consumer.ironmq.api_version"),
						"encryption_key" => \config("consumer.ironmq.encryption_key"),
					])
				),

			"mercure" => $container->has(Mercure::class) ?
				$container->get(Mercure::class) :
				new Mercure(
					hub: \config("publisher.host"),
					token: \config("publisher.mercure.token"),
					httpClient: $container->has(ClientInterface::class) ?
						$container->get(ClientInterface::class) :
						null
				),

			"mock" => $container->has(MockQueue::class) ?
				$container->get(MockQueue::class) :
				new MockQueue,

			"mqtt" => $container->has(Mqtt::class) ?
				$container->get(Mqtt::class) :
				new Mqtt(
					new MqttClient(
						host: \config("consumer.host"),
						port: \config("consumer.port") ?? 1883,
						clientId: \config("consumer.mqtt.client_id"),
						protocol: \config("consumer.mqtt.protocol"),
						repository: $container->has(Repository::class) ?
							$container->get(Repository::class) :
							null,
						logger: $container->has(LoggerInterface::class) ?
							$container->get(LoggerInterface::class) :
							null,
					)
				),

			"null" => $container->has(NullPublisher::class) ?
				$container->get(NullPublisher::class) :
				new NullPublisher(
					receipt: \config("publisher.null.receipt")
				),

			"outbox" => $container->has(Outbox::class) ?
				$container->get(Outbox::class) :
				new Outbox(
					pdo: new PDO(\config("publisher.host"), \config("publisher.outbox.username"), \config("publisher.outbox.password")),
					table: \config("publisher.outbox.table"),
					identity_generator: \config("publisher.outbox.identity_generator")
				),

			"rabbitmq" => $container->has(RabbitMQ::class) ?
				$container->get(RabbitMQ::class) :
				new RabbitMQ(self::getRabbitMQChannel(
					new AMQPStreamConnection(
						host: \config("consumer.host"),
						port: \config("consumer.port"),
						user: \config("consumer.rabbitmq.username"),
						password: \config("consumer.rabbitmq.password"),
						keepalive: \config("consumer.rabbitmq.keepalive"),
					)
				)),

			"redis" => $container->has(RedisQueue::class) ?
				$container->get(RedisQueue::class) :
				new RedisQueue(
					new RedisClient(
						\array_merge([
							"host" => \config("consumer.host"),
							"port" => \config("consumer.port") ?? "6379",
						], \config("consumer.redis.parameters") ?? []),
						\config("consumer.redis.options")
					)
				),

			"redis_pubsub" => $container->has(RedisPubsub::class) ?
				$container->get(RedisPubsub::class) :
				new RedisPubsub(
					client: new RedisClient(
						\config("consumer.host") . ":" . \config("consumer.port") ?? "6379",
						\config("consumer.redis")
					)
				),

			"segment" => $container->has(Segment::class) ?
				$container->get(Segment::class) :
				new Segment(
					client: new SegmentClient("publisher.segment.secret"),
					autoflush: \config("publisher.segment.auto_flush")
				),

			"sns" => $container->has(Sns::class) ?
				$container->get(Sns::class) :
				new Sns(
					new SnsClient(\config("publisher.sns"))
				),

			"sqs" => $container->has(Sqs::class) ?
				$container->get(Sqs::class) :
				new Sqs(
					new SqsClient(\config("consumer.sqs"))
				),

			"webhook" => new Webhook(
				httpClient: $container->has(ClientInterface::class) ?
					$container->get(ClientInterface::class) :
					null,
				hostname: \config("publisher.host"),
				headers: \config("publisher.webhook.headers"),
				method: \config("publisher.webhook.method")
			),

			default => throw new UnexpectedValueException(
				\sprintf("Unknown or unsupported adapter \"%s\".", $adapter)
			),
		};

		return $instance;
	}

	/**
	 * Build the DeadletterPublisher instance.
	 *
	 * @param string|null $adapter
	 * @param string|null $topic
	 * @param Container $container
	 * @return DeadletterPublisher|null
	 */
	private static function resolveDeadletterPublisher(?string $adapter, ?string $topic, Container $container): ?PublisherInterface
	{
		if( empty($adapter) ){
			return null;
		}

		if( empty($topic) ){
			throw new UnexpectedValueException("You must specify a deadletter topic.");
		}

		$publisher = self::resolveAdapter($adapter, $container);

		if( $publisher instanceof PublisherInterface === false ){
			throw new UnexpectedValueException(
				\sprintf("Adapter \"%s\" is not a publisher and cannot be used for deadletter.", $publisher::class)
			);
		}

		return new RedirectFilter($publisher, $topic);
	}

	/**
	 * Build the Rabbit AMQPChannel instance.
	 *
	 * @return AMQPChannel
	 */
	private static function getRabbitMQChannel(AMQPStreamConnection $connection): AMQPChannel
	{
		$channel = $connection->channel();

		if( \config("consumer.rabbitmq.queue") ){
			$channel->queue_declare(
				queue: \config("consumer.rabbitmq.queue"),
				durable: \config("consumer.rabbitmq.durable"),
				auto_delete: \config("consumer.rabbitmq.auto_delete"),
			);
		}

		return $channel;
	}

	/**
	 * Get the configured GearmanClient or GearmanWorker instance.
	 *
	 * @param GearmanClient|GearmanWorker $instance
	 * @param string|array<string> $servers
	 * @param int $options
	 * @return \GearmanClient|\GearmanWorker
	 */
	private static function getGearmanInstance(
		\GearmanClient|\GearmanWorker $instance,
		string|array $servers,
		int $options = 0): \GearmanClient|\GearmanWorker
	{
		if( \is_array($servers) ){
			$servers = \implode(",", $servers);
		}

		$instance->addServers($servers);

		if( $options ){
			$instance->addOptions($options);
		}

		return $instance;
	}
}