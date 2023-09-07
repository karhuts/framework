<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route;

use FastRoute\DataGenerator;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use karthus\route\Strategy\ApplicationStrategy;
use karthus\route\Strategy\StrategyAwareInterface;
use karthus\route\Strategy\StrategyAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_values;
use function is_array;
use function is_file;
use function is_string;

/**
 * Class Router.
 */
class Router implements StrategyAwareInterface, RouteCollectionInterface, RequestHandlerInterface, RouteConditionHandlerInterface
{
    use StrategyAwareTrait;
    use RouteCollectionTrait;
    use RouteConditionHandlerTrait;

    /**
     * @var callable
     */
    protected static $hook;

    protected static string $groupPrefix = '';

    protected $routesData;

    protected static array $patternMatchers = [
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

    protected static ?RouteCollector $routeCollector;

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

    private static array $middlewares = [];

    /**
     * @var Route
     */
    private static $instance;

    public static function group(callable|string $path, callable $callback = null): Route
    {
        if ($callback === null) {
            $callback = $path;
            $path = '';
        }

        $group = new Route(['GROUP'], $path, $callback, true);
        static::$allRoutes[] = $group;
        return $group;
    }

    /**
     * @return Route[]
     */
    public static function getRoutes(): array
    {
        return static::$allRoutes;
    }

    /**
     * @return $this
     */
    public function middleware(mixed $middleware): Router
    {
        foreach ($this->routes as $route) {
            $route->middleware($middleware);
        }

        return $this;
    }

    /**
     * @throws Http\Exception\RouterDomainNotMatchException
     * @throws Http\Exception\RouterPortNotMatchException
     * @throws Http\Exception\RouterSchemeNotMatchException
     */
    public static function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $router = new static();
        $router->prepareRoutes($request);

        $dispatcher = (new Dispatcher($router->routesData))->setStrategy($router->getStrategy() ?? (new ApplicationStrategy()));
        return $dispatcher->dispatchRequest($request);
    }

    public static function addRoute(array|string $methods, string $path, $handler): Route
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        $path = static::$groupPrefix . $path;
        $route = new Route($methods, $path, $handler, false);
        if (static::$middlewares) {
            $route->middleware(static::$middlewares);
        }
        static::$allRoutes[] = $route;
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

        Router::setCollector();
        foreach ($paths as $configPath) {
            $routeConfigFile = $configPath . '/route.php';
            if (is_file($routeConfigFile)) {
                require_once $routeConfigFile;
            }
        }
    }

    /**
     * @throws Http\Exception\RouterDomainNotMatchException
     * @throws Http\Exception\RouterPortNotMatchException
     * @throws Http\Exception\RouterSchemeNotMatchException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return static::dispatch($request);
    }

    public static function hook(callable $callback): void
    {
        static::$hook = $callback;
    }

    public static function getHook(): callable
    {
        return static::$hook;
    }

    /**
     * @throws Http\Exception\RouterDomainNotMatchException
     * @throws Http\Exception\RouterPortNotMatchException
     * @throws Http\Exception\RouterSchemeNotMatchException
     */
    protected function prepareRoutes(ServerRequestInterface $request): void
    {
        // 先搞 group
        $routes = static::$allRoutes;
        // 遍历路由规则
        foreach ($routes as $key => $route) {
            if ($route->isGroup()) {
                static::$groupPrefix = $route->getPath();
                static::$middlewares = $route->getMiddleware();
                $route->getCallback()();
                unset(static::$allRoutes[$key]);
            }
        }
        $routes = static::$allRoutes;
        $collector = static::$routeCollector;
        foreach ($routes as $route) {
            if ($this->isExtraConditionMatch($route, $request) === false) {
                continue;
            }
            if ($route->getStrategy() === null) {
                $strategy = $this->getStrategy() ?? new ApplicationStrategy();
                $route->setStrategy($strategy);
            }
            $path = static::parseRoutePath($route->getPath());
            $collector->addRoute($route->getMethods(), $path, $route);
        }
        $this->routesData = $collector->getData();
    }

    protected static function parseRoutePath(string $path): string
    {
        return preg_replace(array_keys(static::$patternMatchers), array_values(static::$patternMatchers), $path);
    }

    private static function setCollector(): void
    {
        static::$routeCollector = static::$routeCollector ?? new RouteCollector(
            new RouteParser\Std(),
            new DataGenerator\GroupCountBased()
        );
    }
}
