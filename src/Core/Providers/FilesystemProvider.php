<?php

namespace Nimbly\Foundation\Core\Providers;

use Aws\S3\S3Client;
use Nimbly\Carton\Container;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use Nimbly\Carton\ServiceProviderInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;

/**
 * Provides a `League\Flysystem` instance to be used in global file
 * operations (reading, writing, listing, etc).
 *
 * @see `config/filesystem.php` for configuration options.
 */
class FilesystemProvider implements ServiceProviderInterface
{
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$container->singleton(
			Filesystem::class,
			function(Container $container): Filesystem {
				$adapter = match(\config("filesystem.adapter")) {
					"local" => new LocalFilesystemAdapter(
						\config("filesystem.path"),
					),

					"memory" => new InMemoryFilesystemAdapter,

					"s3" => new AwsS3V3Adapter(
						$container->get(S3Client::class),
						\config("filesystem.path")
					),

					"azure" => new AzureBlobStorageAdapter(
						$container->get(BlobRestProxy::class),
						\config("filesystem.path"),
					),

					"ftp" => new FtpAdapter(
						FtpConnectionOptions::fromArray([
							"host" => \config("filesystem.remote.host"),
							"root" => \config("filesystem.path"),
							"username" => \config("filesystem.remote.username"),
							"password" => \config("filesystem.remote.password"),
							"port" => \config("filesystem.remote.port") ?? 20,
							"ssl" => \config("filesystem.remote.ftp.ssl") ?? false,
							"timeout" => \config("filesystem.remote.timeout") ?? 10,
							"utf8" => false,
							"passive" => \config("filesystem.remote.ftp.passive") ?? true,
							"transferMode" => \config("filesystem.remote.ftp.mode") ?? FTP_BINARY,
							"systemType" => null, // "windows" or "unix"
							"ignorePassiveAddress" => null, // true or false
							"timestampsOnUnixListingsEnabled" => false, // true or false
							"recurseManually" => true // true
						])
					),

					"sftp" => new SftpAdapter(
						new SftpConnectionProvider(
							host: \config("filesystem.remote.host"),
							username: \config("filesystem.remote.username"),
							password: \config("filesystem.remote.password"),
							privateKey: \config("filesystem.remote.sftp.private_key"),
							passphrase: \config("filesystem.remote.sftp.passphrase"),
							port: \config("filesystem.remote.port") ?? 22,
							useAgent: false,
							timeout: \config("filesystem.remote.timeout", 10),
							maxTries: 4,
							hostFingerprint: \config("filesystem.remote.sftp.fingerprint"),
							connectivityChecker: null,
						),
						\config("filesystem.path")
					)
				};

				if( \config("filesystem.read_only") ){
					$adapter = new ReadOnlyFilesystemAdapter($adapter);
				}

				if( \config("filesystem.path_prefix") ){
					$adapter = new PathPrefixedAdapter($adapter, \config("filesystem.path_prefix"));
				}

				return new Filesystem($adapter);
			}
		);
	}
}