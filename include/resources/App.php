<?php declare( strict_types=1 );

namespace TemplifyWP\TemplifyImporterTemplates;

use InvalidArgumentException;
use TemplifyWP\TemplifyImporterTemplates\Cache\Cache_Provider;
use TemplifyWP\TemplifyImporterTemplates\Image_Downloader\Image_Downloader_Provider;
use TemplifyWP\TemplifyImporterTemplates\Shutdown\Shutdown_Provider;
use TemplifyWP\TemplifyImporterTemplates\StellarWP\ProphecyMonorepo\Container\Contracts\Container;
use TemplifyWP\TemplifyImporterTemplates\StellarWP\ProphecyMonorepo\Container\Contracts\Providable;
use RuntimeException;

/**
 * The Core Templify Blocks Application, with container support.
 */
final class App {

	private static $instance;

	/**
	 * @var Container
	 */
	private $container;

	/**
	 * Add any custom providers here.
	 *
	 * @note The order is important.
	 *
	 * @var class-string<Providable>
	 */
	private $providers = array(
		Image_Downloader_Provider::class,
		Cache_Provider::class,
		Shutdown_Provider::class,
	);

	private function __construct(
		Container $container
	) {
		$this->container = $container;

		$this->init();
	}

	/**
	 * @param Container|null $container
	 *
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public static function instance( ?Container $container = null ): App {
		if ( ! isset( self::$instance ) ) {
			if ( ! $container ) {
				throw new InvalidArgumentException( 'You need to provide a concrete Contracts\Container instance!' );
			}

			self::$instance = new self( $container );
		}

		return self::$instance;
	}

	public function container(): Container {
		return $this->container;
	}

	private function init(): void {
		$this->container->bind( Container::class, $this->container );

		foreach ( $this->providers as $provider ) {
			$this->container->register( $provider );
		}
	}

	private function __clone() {
	}

	public function __wakeup(): void {
		throw new RuntimeException( 'method not implemented' );
	}

	public function __sleep(): array {
		throw new RuntimeException( 'method not implemented' );
	}

}
