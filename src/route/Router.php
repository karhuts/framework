<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route;

use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use karthus\route\Strategy\StrategyAwareInterface;
use karthus\route\Strategy\StrategyAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_values;
use function FastRoute\simpleDispatcher;
use function is_array;
use function is_file;
use function is_string;

/**
 * Class Router.
 */
class Router implements StrategyAwareInterface, RouteCollectionInterface, RequestHandlerInterface
{
    use StrategyAwareTrait;
    use RouteCollectionTrait;

    /**
     * @var callable
     */
    protected static $hook;

    protected static string $groupPrefix = '';

    /**
     * @var array
     */
    protected static $patternMatchers = [
        '/{(.+?):number}/' => '{$1:[0-9]+}',
        '/{(.+?):word}/' => '{$1:[a-zA-Z]+}',
        '/{(.+?):alphanum_dash}/' => '{$1:[a-zA-Z0-9-_]+}',
        '/{(.+?):slug}/' => '{$1:[a-z0-9-]+}',
        '/{(.+?):uuid}/' => '{$1:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}+}',
    ];

    /**
     * router group.
     */
    protected static array $groups = [];

    /**
     * @var RouteCollector
     */
    protected static $routeCollector;

    /**
     * @var GroupCountBased
     */
    protected static $dispatcher;

    /**
     * @var Route[]
     */
    protected static array $allRoutes = [];

    /**
     * @var Route[]
     */
    protected array $routes = [];

    /**
     * @var Route
     */
    private static $instance;

    /**
     * @param callable|string $path
     * @param callable|null $callback
     * @return Router
     */
    public static function group(callable|string $path, callable $callback = null): Router
    {
        if ($callback === null) {
            $callback = $path;
            $path = '';
        }
        $previousGroupPrefix = static::$groupPrefix;
        static::$groupPrefix = $previousGroupPrefix . $path;
        $previousInstance = static::$instance;

        $instance = static::$instance = new static();
        static::$routeCollector->addGroup($path, $callback);
        static::$groupPrefix = $previousGroupPrefix;
        static::$instance = $previousInstance;

        if ($previousInstance) {
            $previousInstance->addChild($instance);
        }
        return $instance;
    }

    public function addChild(Route $route): void
    {
        $this->children[] = $route;
    }

    /**
     * @return Route[]
     */
    public static function getRoutes(): array
    {
        return static::$allRoutes;
    }

    /**
     * @param mixed $middleware
     * @return $this
     */
    public function middleware($middleware): Router
    {
        foreach ($this->routes as $route) {
            $route->middleware($middleware);
        }

        return $this;
    }

    public static function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $dispatcher = new Dispatcher();
        return $dispatcher->dispatchRequest(static::$dispatcher, $request);
    }

    public static function addRoute(array|string $methods, string $path, $handler): Route
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        $path = static::parseRoutePath($path);
        $route = new Route($methods, $path, $handler);
        static::$allRoutes[] = $route;

        $callback = $handler;
        static::$routeCollector->addRoute($methods, $path, ['callback' => $callback, 'route' => $route]);

        if (static::$instance) {
            static::$instance->collect($route);
        }

        return $route;
    }

    /**
     * Load.
     */
    public static function load(mixed $paths): void
    {
        if (! is_array($paths)) {
            return;
        }

        static::$dispatcher = simpleDispatcher(function (RouteCollector $route) use ($paths) {
            Router::setCollector($route);
            foreach ($paths as $configPath) {
                $routeConfigFile = $configPath . '/route.php';
                if (is_file($routeConfigFile)) {
                    require_once $routeConfigFile;
                }
            }
        });
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return static::dispatch($request);
    }

    public function collect(Route $route): void
    {
        $this->routes[] = $route;
    }

    public static function hook(callable $callback): void
    {
        static::$hook = $callback;
    }

    public static function getHook(): callable
    {
        return static::$hook;
    }

    protected static function parseRoutePath(string $path): string
    {
        return preg_replace(array_keys(static::$patternMatchers), array_values(static::$patternMatchers), $path);
    }

    private static function setCollector(RouteCollector $route): void
    {
        static::$routeCollector = $route;
    }
}
