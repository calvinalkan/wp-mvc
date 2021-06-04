<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;



	use BetterWpHooks\Traits\DispatchesConditionally;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;

    class OutputBufferRequired extends IncomingRequest {

		use DispatchesConditionally;

		public function __construct(Request $request) {

			parent::__construct($request);

		}

		public function shouldDispatch() : bool {

			return WP::isAdmin();

		}

	}