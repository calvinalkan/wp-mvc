<?php


	namespace WPEmerge\Routing;

	class RouteMatch {


		/** @var Route */
		private $route;

		/** @var array */
		private $payload;


		public function __construct( ?Route $route, array $payload ) {

			$this->route   = $route;
			$this->payload = $payload;

		}

		public function route() : ?Route {

			return $this->route;
		}


		public function payload() : array {

			return $this->payload;
		}


	}