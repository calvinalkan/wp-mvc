<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

    use Tests\unit\View\MethodField;
    use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFactoryInterface;
	use WPEmerge\Factories\ViewComposerFactory;


	class ViewServiceProvider extends ServiceProvider {

		public function register() : void {


		    $this->bindConfig();

		    $this->bindMethodField();

		    $this->bindGlobalContext();

            $this->bindViewServiceImplementation();

            $this->bindViewServiceInterface();

			$this->bindPhpViewEngine();

			$this->bindViewEngineInterface();

			$this->bindViewComposerCollection();


		}

		public function bootstrap() : void {
			// Nothing to bootstrap.
		}

        private function bindConfig()
        {

            $ds = DIRECTORY_SEPARATOR;
            $dir = dirname(__FILE__ , 3).$ds.'resources'.$ds.'views';
            $views = $this->config->get('views', []);
            $views = array_merge($views, [$dir]);
            $this->config->set('views', $views);

        }

        private function bindGlobalContext()
        {
            $this->container->instance(GlobalContext::class, new GlobalContext());

        }

        private function bindViewServiceInterface() : void
        {

            $this->container->singleton(ViewFactoryInterface::class, function () {

                return $this->container->make(ViewFactory::class);

            });
        }

        private function bindViewServiceImplementation() : void
        {

            $this->container->singleton(ViewFactory::class, function () {

                return new ViewFactory(
                    $this->container->make(ViewEngineInterface::class),
                    $this->container->make(ViewComposerCollection::class),
                    $this->container->make(GlobalContext::class)

                );

            });
        }

        private function bindPhpViewEngine() : void
        {

            $this->container->singleton(PhpViewEngine::class, function () {

                return new PhpViewEngine(
                    new PhpViewFinder($this->config->get('views', []))
                );

            });
        }

        private function bindViewEngineInterface() : void
        {

            $this->container->singleton(ViewEngineInterface::class, function () {

                return $this->container->make(PhpViewEngine::class);

            });
        }

        private function bindViewComposerCollection() : void
        {

            $this->container->singleton(ViewComposerCollection::class, function () {

                return new ViewComposerCollection(
                    $this->container->make(ViewComposerFactory::class),
                );

            });
        }

        private function bindMethodField()
        {
            $this->container->singleton(MethodField::class, function () {
                return new MethodField($this->appKey());
            });
        }

    }
