<?php


    declare(strict_types = 1);


    namespace WPMvc\Application;

    use WPMvc\View\MethodField;
    use WPMvc\Contracts\AbstractRedirector;
    use WPMvc\Contracts\ServiceProvider;
    use WPMvc\Contracts\ViewFactoryInterface;
    use WPMvc\Support\WP;
    use WPMvc\Http\ResponseFactory;
    use WPMvc\ExceptionHandling\Exceptions\ConfigurationException;
    use WPMvc\ExceptionHandling\ShutdownHandler;
    use WPMvc\Http\Cookies;
    use WPMvc\Routing\Router;
    use WPMvc\Routing\UrlGenerator;
    use WPMvc\View\GlobalContext;
    use WPMvc\View\PhpViewEngine;
    use WPMvc\View\ViewComposerCollection;

    class ApplicationServiceProvider extends ServiceProvider
    {

        public function register() : void
        {
            $this->bindConfig();
            $this->bindShutDownHandler();
            $this->bindAliases();
        }

        public function bootstrap() : void
        {

            if ( !  $this->validAppKey() && ! $this->app->isRunningUnitTest() ) {

                throw new ConfigurationException('Your app_key is either missing or too insecure.');

            }

        }

        private function bindShutDownHandler()
        {

            $this->container->singleton(ShutdownHandler::class, function () {

                return new ShutdownHandler($this->app->isRunningUnitTest());

            });
        }

        private function bindConfig()
        {

            $this->config->extend('app.package_root', dirname(__FILE__, 3) );
            $this->config->extend('app.storage_dir', $this->app->basePath(). DIRECTORY_SEPARATOR . 'storage');
            $this->config->extend('app.url', WP::siteUrl());
            $this->config->extend('app.exception_handling', true);
            $this->config->extend('app.debug', true );

        }

        private function bindAliases()
        {

            $app = $this->container->make(Application::class);

            $this->applicationAliases($app);
            $this->responseAliases($app);
            $this->routingAliases($app);
            $this->viewAliases($app);

        }

        private function applicationAliases(Application $app)
        {

            $app->alias('app', Application::class);

        }

        private function responseAliases(Application $app)
        {

            $app->alias('cookies', Cookies::class);
            $app->alias('response', ResponseFactory::class);
            $app->alias('redirect', function (?string $path = null , int $status = 302 ) use ($app) {

                /** @var AbstractRedirector $redirector */
                $redirector = $app->resolve(AbstractRedirector::class);

                if ( $path ) {

                    return $redirector->to($path, $status);

                }

                return $redirector;

            });

        }

        private function routingAliases(Application $app)
        {

            $app->alias('route', Router::class);
            $app->alias('url', UrlGenerator::class);
            $app->alias('routeUrl', UrlGenerator::class, 'toRoute');
            $app->alias('post', Router::class, 'post');
            $app->alias('get', Router::class, 'get');
            $app->alias('patch', Router::class, 'patch');
            $app->alias('put', Router::class, 'put');
            $app->alias('options', Router::class, 'options');
            $app->alias('delete', Router::class, 'delete');
            $app->alias('match', Router::class, 'match');

        }

        private function viewAliases(Application $app)
        {

            $app->alias('globals', function () use ($app) {

                /** @var GlobalContext $globals */
                $globals = $app->resolve(GlobalContext::class);

                $globals->add(...array_values(func_get_args()));

            });
            $app->alias('addComposer', function () use ($app) {

                $composer_collection = $app->resolve(ViewComposerCollection::class);

                $args = func_get_args();

                $composer_collection->addComposer(...$args);

            });
            $app->alias('view', function () use ($app) {

                /** @var ViewFactoryInterface $view_service */
                $view_service = $app->container()->make(ViewFactoryInterface::class);

                return call_user_func_array([$view_service, 'make'], func_get_args());

            });
            $app->alias('render', function () use ($app) {

                /** @var ViewFactoryInterface $view_service */
                $view_service = $app->container()->make(ViewFactoryInterface::class);

                $view_as_string = call_user_func_array([$view_service, 'render',], func_get_args());

                echo $view_as_string;

            });
            $app->alias('includeChildViews', function () use ($app) {

                /** @var PhpViewEngine $engine */
                $engine = $app->resolve(PhpViewEngine::class);

                $engine->includeNextView();


            });
            $app->alias('methodField', MethodField::class, 'html');

        }


    }
