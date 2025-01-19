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
use Nimbly\Syndicate\Queue\RabbitMQ;
use Nimbly\Syndicate\Queue\Sqs;
use Nimbly\Syndicate\PubSub\Sns;
use Nimbly\Syndicate\Queue\Iron;
use Nimbly\Syndicate\Application;
use Nimbly\Syndicate\PubSub\Mock;
use Nimbly\Syndicate\PubSub\Mqtt;
use Nimbly\Syndicate\Queue\Azure;
use Nimbly\Syndicate\PubSub\Google;
use PhpAmqpLib\Channel\AMQPChannel;
use Google\Cloud\PubSub\PubSubClient;
use Nimbly\Syndicate\Queue\Beanstalk;
use Nimbly\Syndicate\RouterInterface;
use Nimbly\Syndicate\ConsumerInterface;
use Nimbly\Syndicate\PublisherInterface;
use PhpMqtt\Client\Contracts\Repository;
use Nimbly\Syndicate\DeadletterPublisher;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Syndicate\Queue\Redis as RedisQueue;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use Nimbly\Syndicate\PubSub\Redis as RedisPubsub;
use Nimbly\Syndicate\Router;

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
							\config("consumer.handlers") ?? [],
							\config("consumer.default_handler")
						),
					container: $container,
					signals: \config("consumer.signals") ?? [SIGHUP, SIGINT, SIGTERM],
					deadletter: $container->has(DeadletterPublisher::class) ?
						$container->get(DeadletterPublisher::class) :
						self::resolveDeadletterPublisher(
							\config("consumer.deadletter.adapter"),
							\config("consumer.deadletter.topic"),
							$container
						),
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
					QueueRestProxy::createQueueService(
						\config("consumer.azure.connection_string")
					)
				),

			"beanstalkd" => $container->has(Beanstalk::class) ?
				$container->get(Beanstalk::class) :
				new Beanstalk(
					Pheanstalk::create(
						\config("consumer.host"),
						\config("consumer.port"),
					)
				),

			"google" => $container->has(Google::class) ?
				$container->get(Google::class) :
				new Google(
					new PubSubClient()
				),

			"ironmq" => $container->has(Iron::class) ?
				$container->get(Iron::class) :
				new Iron(
					new IronMQ([
						"token" => \config("consumer.ironmq.token"),
						"project_id" => \config("consumer.ironmq.project_id"),
						"protocol" => \config("consumer.ironmq.protocol"),
						"host" => \config("consumer.host"),
						"port" => \config("consumer.port"),
						"api_version" => \config("consumer.ironmq.api_version"),
						"encryption_key" => \config("consumer.ironmq.encryption_key"),
					])
				),

			"mock" => $container->has(Mock::class) ?
				$container->get(Mock::class) :
				new Mock,

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
					new RedisClient(
						\config("consumer.host") . ":" . \config("consumer.port") ?? "6379",
						\config("consumer.redis")
					)
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

			default => throw new UnexpectedValueException("Unknown adapter: " . $adapter),
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
	private static function resolveDeadletterPublisher(?string $adapter, ?string $topic, Container $container): ?DeadletterPublisher
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

		return new DeadletterPublisher($publisher, $topic);
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
}