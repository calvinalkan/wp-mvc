<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use BetterWpHooks\Traits\DispatchesConditionally;
	use WPEmerge\Contracts\RequestInterface;
    use WPEmerge\Http\Request;

    class IncomingWebRequest extends IncomingRequest {

		use DispatchesConditionally;

		/**
		 * @var string
		 */
		public $template;


		public function __construct( string $template, Request $request ) {

			$this->template = $template;

			parent::__construct($request);

			$this->request->withType( get_class( $this ) );


		}

		public function shouldDispatch() : bool {

			return ! is_admin() && ! str_contains( $this->request->url(), admin_url() );

		}


		public function default() : ?string {

			if ( ! $this->has_matching_route && ! $this->force_route_match ) {

				return $this->template;

			}

			return null;

		}


	}