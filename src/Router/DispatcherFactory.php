<?php
declare(strict_types=1);


namespace Karthus\Router;

use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteParser\Std;
use Karthus\Annotation\Controller;
use Karthus\Annotation\DeleteMapping;
use Karthus\Annotation\GetMapping;
use Karthus\Annotation\Mapping;
use Karthus\Annotation\Middleware;
use Karthus\Annotation\Middlewares;
use Karthus\Annotation\PatchMapping;
use Karthus\Annotation\PostMapping;
use Karthus\Annotation\PutMapping;
use Karthus\Annotation\RequestMapping;
use Karthus\Exception\ConflictAnnotationException;
use Karthus\Functions\Strings;
use ReflectionMethod;

class DispatcherFactory {
    protected $routes = [BASE_PATH . '/config/routes.php'];
    /**
     * @var \FastRoute\RouteCollector[]
     */
    protected $routers = [];
    /**
     * @var Dispatcher[]
     */
    protected $dispatchers = [];

    /***
     * DispatcherFactory constructor.
     */
    public function __construct() {
        $this->initConfigRoute();
    }

    /**
     * @param string $serverName
     * @return Dispatcher
     */
    public function getDispatcher(string $serverName): Dispatcher {
        if (isset($this->dispatchers[$serverName])) {
            return $this->dispatchers[$serverName];
        }
        $router = $this->getRouter($serverName);
        return $this->dispatchers[$serverName] = new GroupCountBased($router->getData());
    }

    /**
     * 初始化
     */
    public function initConfigRoute() {
        Router::init($this);
        foreach ($this->routes as $route) {
            if (file_exists($route)) {
                require_once $route;
            }
        }
    }

    /**
     * @param string $serverName
     * @return RouteCollector
     */
    public function getRouter(string $serverName): RouteCollector {
        if (isset($this->routers[$serverName])) {
            return $this->routers[$serverName];
        }
        $parser = new Std();
        $generator = new DataGenerator();
        return $this->routers[$serverName] = new RouteCollector($parser, $generator, $serverName);
    }

    /**
     * @param array $collector
     * @throws ConflictAnnotationException
     */
    protected function initAnnotationRoute(array $collector): void {
        foreach ($collector as $className => $metadata) {
            if (isset($metadata['_c'][Controller::class])) {
                $middlewares = $this->handleMiddleware($metadata['_c']);
                $this->handleController($className, $metadata['_c'][Controller::class], $metadata['_m'] ?? [], $middlewares);
            }
        }
    }

    /**
     * Register route according to Controller and XxxMapping annotations.
     * Including RequestMapping, GetMapping, PostMapping, PutMapping, PatchMapping, DeleteMapping.
     *
     * @param string     $className
     * @param Controller $annotation
     * @param array      $methodMetadata
     * @param array      $middlewares
     * @throws ConflictAnnotationException
     */
    protected function handleController(string $className, Controller $annotation, array $methodMetadata, array $middlewares = []): void {
        if (! $methodMetadata) {
            return;
        }
        $prefix = $this->getPrefix($className, $annotation->prefix);
        $router = $this->getRouter($annotation->server);
        $mappingAnnotations = [
            RequestMapping::class,
            GetMapping::class,
            PostMapping::class,
            PutMapping::class,
            PatchMapping::class,
            DeleteMapping::class,
        ];
        foreach ($methodMetadata as $methodName => $values) {
            $methodMiddlewares = $middlewares;
            // Handle method level middlewares.
            if (isset($values)) {
                $methodMiddlewares = array_merge($methodMiddlewares, $this->handleMiddleware($values));
                $methodMiddlewares = array_unique($methodMiddlewares);
            }
            foreach ($mappingAnnotations as $mappingAnnotation) {
                /** @var Mapping $mapping */
                if ($mapping = $values[$mappingAnnotation] ?? null) {
                    if (! isset($mapping->path) || ! isset($mapping->methods)) {
                        continue;
                    }
                    $path = $mapping->path;
                    if ($path === '') {
                        $path = $prefix;
                    } elseif ($path[0] !== '/') {
                        $path = $prefix . '/' . $path;
                    }
                    $router->addRoute($mapping->methods, $path, [$className, $methodName], [
                        'middleware' => $methodMiddlewares,
                    ]);
                }
            }
        }
    }

    /**
     * @param string $className
     * @param string $prefix
     * @return string
     */
    protected function getPrefix(string $className, string $prefix): string {
        if (! $prefix) {
            $handledNamespace = Strings::replaceFirst('Controller', '', Strings::after($className, '\\Controller\\'));
            $handledNamespace = str_replace('\\', '/', $handledNamespace);
            $prefix = Strings::snake($handledNamespace);
            $prefix = str_replace('/_', '/', $prefix);
        }
        if ($prefix[0] !== '/') {
            $prefix = '/' . $prefix;
        }
        return $prefix;
    }

    /**
     * @param string           $prefix
     * @param ReflectionMethod $method
     * @return string
     */
    protected function parsePath(string $prefix, ReflectionMethod $method): string {
        return $prefix . '/' . $method->getName();
    }

    /**
     * @param array $item
     * @return bool
     */
    protected function hasControllerAnnotation(array $item): bool {
        return isset($item[Controller::class]);
    }

    /**
     * @param array $metadata
     * @return array
     * @throws ConflictAnnotationException
     */
    protected function handleMiddleware(array $metadata): array {
        $hasMiddlewares = isset($metadata[Middlewares::class]);
        $hasMiddleware = isset($metadata[Middleware::class]);
        if (! $hasMiddlewares && ! $hasMiddleware) {
            return [];
        }
        if ($hasMiddlewares && $hasMiddleware) {
            throw new ConflictAnnotationException('Could not use @Middlewares and @Middleware annotation at the same times at same level.');
        }
        if ($hasMiddlewares) {
            // @Middlewares
            /** @var Middlewares $middlewares */
            $middlewares = $metadata[Middlewares::class];
            $result = [];
            foreach ($middlewares->middlewares as $middleware) {
                $result[] = $middleware->middleware;
            }
            return $result;
        }
        // @Middleware
        /** @var Middleware $middleware */
        $middleware = $metadata[Middleware::class];
        return [$middleware->middleware];
    }
}
