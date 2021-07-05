<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Controllers\Admin;

    use Tests\fixtures\Middleware\MiddlewareWithDependencies;
	use WPMvc\Http\Controller;
	use Tests\fixtures\TestDependencies\Baz;
	use WPMvc\Http\Psr7\Request;

	class AdminControllerWithMiddleware extends Controller {

		/**
		 * @var Baz
		 */
		private $baz;

		const constructed_times = 'controller_with_middleware';

		public function __construct( Baz $baz ) {

			$this->middleware(MiddlewareWithDependencies::class);

			$this->baz = $baz;

			$count = $GLOBALS['test'][ self::constructed_times ] ?? 0;
			$count ++;
			$GLOBALS['test'][ self::constructed_times ] = $count;

		}

		public function handle( Request $request ) : string
        {

			$request->body .= $this->baz->baz . ':controller_with_middleware';

			return $request->body;

		}


	}

