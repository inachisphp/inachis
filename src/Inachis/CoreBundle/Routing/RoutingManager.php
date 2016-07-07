<?php
namespace Inachis\Component\CoreBundle\Routing;

use Klein\Klein;
use Inachis\Component\CoreBundle\Application;
use Inachis\Component\CoreBundle\Routing\Route;
use Inachis\Component\CoreBundle\Configuration\ConfigManager;

class RoutingManager
{
    /**
     * @var RoutingManager The instance of {@link RoutingManager}
     */
    protected static $instance;
    /**
     * @var Klein\Klein Instance of {@link Klein\Klein} used for routing
     */
    protected $klein;
    /**
     * Default constructor sets up instance of Klein\Klein and
     * adds default routes
     */
    public function __construct()
    {
        $this->klein = new Klein();
        $this->addDefaultRoutes();
    }
    /**
     * Returns an instance of {@link RoutingManager}
     * @return RoutingManager The current or a new instance of {@link RoutingManager}
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * Returns the object containing the current request
     * @return Request The current request object
     */
    public function getRequest()
    {
        return $this->klein->request();
    }
    /**
     * Load additional routing configuration using {@link ConfigManager} from the
     * config/routing/ folder. All JSON files in this folder will be parsed
     */
    public function load()
    {
        $routes = ConfigManager::getInstance()->loadAllFromLocation('routing', 'json');
        foreach ($routes as $routeNamespace) {
            if (empty($routeNamespace)) {
                throw new \Exception('No route namespaces defined; please check the application config.');
            }
            foreach ($routeNamespace as $route) {
                $importedRoute = new Route();
                $this->addRoute($importedRoute->hydrate($route));
            }
        }
    }
    /**
     * Adds a route to {@link Klein\Klein} from the details specified in the provided {@link Route}
     * @param Route $route The route details containing methods, path, and action
     */
    public function addRoute(Route $route)
    {
        $this->klein->respond(
            $route->getMethods(),
            $route->getPath(),
            $route->getAction()
        );
    }
    /**
     * Tells {@link Klein\Klein} to handle the current request
     */
    public function dispatch()
    {
        $this->klein->dispatch();
    }
    /**
     * Adds view handler and error handlers to routing
     */
    public function addDefaultRoutes()
    {
        $this->registerViewHandler();
        $this->registerErrorHandlers();
    }
    /**
     * Registers the Twig view handlers
     * @return Twig_Environment The twif view handler
     */
    public function registerViewHandler()
    {
        $router = $this->klein;
        $this->klein->respond(function ($request, $response, $service, $app) use ($router) {
            $app->register('twig', function () {
                $loader = new \Twig_Loader_Filesystem(array(
                    Application::getInstance()->getApplicationRoot() . 'resources/views/',
                    Application::getInstance()->getApplicationRoot() . 'src/Inachis/CoreBundle/views/'
                ));
                $env = Application::getInstance()->getEnv();
                $options = array();
                if ($env === 'dev') {
                    $options['debug'] = true;
                } elseif (in_array($env, array('preprod', 'prod'))) {
                    $options['cache'] = true;
                };
                if (!empty(Application::getInstance()->getConfig()['twig']->twig->options)) {
                    foreach (Application::getInstance()->getConfig()['twig']->twig->options as $optionName => $optionValue) {
                        $options[$optionName] = $optionValue;
                    }
                }
                $twig = new \Twig_Environment($loader, $options);
                if (!empty(Application::getInstance()->getConfig()['twig']->twig->extensions)) {
                    foreach (Application::getInstance()->getConfig()['twig']->twig->extensions as $extension) {
                        $twig->addExtension(new $extension());
                    }
                }
                if ($env === 'dev') {
                    $twig->addExtension(new \Twig_Extension_Debug());
                }
                return $twig;
            });
        });
    }
    /**
     * Adds default responder routes for error handling and standard admin interface pages
     */
    private function registerErrorHandlers()
    {
        $router = $this->klein;
        $this->klein->onHttpError(function ($code, $router) {
            if ($code >= 400 && $code < 500) {
                // @todo replace with templated error page
                $router->response()->body(
                    'Nothing to see here, you may go about your business. Error: ' . $code
                );
                //$router->response->file(__DIR__ . '404.html');
            } elseif ($code >= 500 && $code <= 599) {
                // @todo replace with templated error page
                $router->response()->body(
                    'Something went a bit wrong. Error: ' . $code
                );
            }
        });
    }
}
