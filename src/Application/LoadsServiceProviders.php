<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Application;

	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Contracts\ServiceProvider;

	/**
	 *
	 * @todo need test for all Service Providers to check if we wired everything correctly.
	 *
	 *
	 */
	trait LoadsServiceProviders {


		/**
		 *
		 * Register and bootstrap all service providers.
		 *
		 */
		public function loadServiceProviders() : void {

			$user_providers = $this->config->get( 'providers', [] );

			$providers = collect( self::CORE_SERVICE_PROVIDERS )->merge( $user_providers );

			$providers->each( [ $this, 'isValid' ] )
			          ->map(  [ $this, 'instantiate' ] )
			          ->each( [ $this, 'register' ] )
			          ->each( [ $this, 'bootstrap' ] );


		}


		/**
		 * @param  string  $provider
		 *
		 * @throws ConfigurationException
		 */
		private function isValid( string $provider ) {

			if ( ! is_subclass_of( $provider, ServiceProvider::class ) ) {
				throw new ConfigurationException(
					'The following class does not implement ' .
					ServiceProvider::class . ': ' . $provider
				);
			}

		}

		/**
		 * @param  string  $provider
		 *
		 * @return ServiceProvider
		 */
		private function instantiate( string $provider ) : ServiceProvider {

			return new $provider( $this->container(), $this->config );

		}

		private function register( ServiceProvider $provider ) {

			$provider->register();

		}

		private function bootstrap( ServiceProvider $provider ) {

			$provider->bootstrap();

		}

	}
