<?php


    declare(strict_types = 1);


    namespace WPMvc\Middleware\Core;

    use Closure;
    use Contracts\ContainerAdapter;
    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\Psr7\Response;
    use WPMvc\Http\ResponseFactory;
    use WPMvc\Middleware\MiddlewareStack;
    use WPMvc\Routing\Pipeline;
    use WPMvc\Routing\Route;
    use WPMvc\Routing\RoutingResult;

    class RouteRunner extends Middleware
    {

        /**
         * @var Pipeline
         */
        private $pipeline;

        /**
         * @var MiddlewareStack
         */
        private $middleware_stack;

        /**
         * @var ContainerAdapter
         */
        private $container;

        public function __construct(ResponseFactory $response_factory, ContainerAdapter $container, Pipeline $pipeline, MiddlewareStack $middleware_stack)
        {

            $this->response_factory = $response_factory;
            $this->pipeline = $pipeline;
            $this->middleware_stack = $middleware_stack;
            $this->container = $container;

        }

        public function handle(Request $request, Delegate $next) :ResponseInterface
        {

            $this->rebindRequest($request);

            $route_result = $request->routingResult();

            if ( ! $route = $route_result->route()) {

                return $this->response_factory->null();

            }

            // The Middleware Pipeline is created within the FallbackController::class
            if ( $route->isFallback() ) {

                return $this->runFallbackRouteController($route, $request);

            }

            $middleware = $this->middleware_stack->createFor($route, $request);


            return $this->pipeline
                ->send($request)
                ->through($middleware)
                ->then($this->runRoute($route_result));


        }

        private function runRoute(RoutingResult $routing_result) : Closure
        {

            return function (Request $request) use ($routing_result) {

                $response = $routing_result->route()->run(
                    $request,
                    $routing_result->capturedUrlSegmentValues()
                );

                return $this->response_factory->toResponse($response);

            };

        }

        private function runFallbackRouteController(Route $route, Request $request) : Response
        {

            return $this->response_factory->toResponse($route->run($request));

        }

        private function rebindRequest(Request $request)
        {
            $this->container->instance(Request::class, $request);
        }

    }