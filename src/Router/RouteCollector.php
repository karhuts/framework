<?php
declare(strict_types=1);

namespace Karthus\Router;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;

class RouteCollector {
    /**
     * @var string
     */
    protected $server;
    /**
     * @var RouteParser
     */
    protected $routeParser;
    /**
     * @var DataGenerator
     */
    protected $dataGenerator;
    /**
     * @var string
     */
    protected $currentGroupPrefix;
    /**
     * @var array
     */
    protected $currentGroupOptions = [];

    /**
     * RouteCollector constructor.
     *
     * @param RouteParser   $routeParser
     * @param DataGenerator $dataGenerator
     * @param string        $server
     */
    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator, string $server = 'http') {
        $this->routeParser = $routeParser;
        $this->dataGenerator = $dataGenerator;
        $this->currentGroupPrefix = '';
        $this->server = $server;
    }

    /**
     * Adds a route to the collection.
     *
     * The syntax used in the $route string depends on the used route parser.
     *
     * @param string|string[] $httpMethod
     * @param array|string $handler
     */
    public function addRoute($httpMethod, string $route, $handler, array $options = []) {
        $route = $this->currentGroupPrefix . $route;
        $routeDatas = $this->routeParser->parse($route);
        $options = $this->mergeOptions($this->currentGroupOptions, $options);
        foreach ((array) $httpMethod as $method) {
            $method = strtoupper($method);
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, new Handler($handler, $route));
                MiddlewareManager::addMiddlewares($this->server, $route, $method, $options['middleware'] ?? []);
            }
        }
    }

    /**
     * Create a route group with a common prefix.
     *
     * All routes created in the passed callback will have the given group prefix prepended.
     *
     * @param string   $prefix
     * @param callable $callback
     * @param array    $options
     */
    public function addGroup(string $prefix, callable $callback, array $options = []) {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $currentGroupOptions = $this->currentGroupOptions;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $this->currentGroupOptions = $this->mergeOptions($currentGroupOptions, $options);
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupOptions = $currentGroupOptions;
    }

    /**
     * Adds a GET route to the collection.
     *
     * This is simply an alias of $this->addRoute('GET', $route, $handler)
     *
     * @param string       $route
     * @param array|string $handler
     * @param array        $options
     */
    public function get(string $route, $handler, array $options = []) {
        $this->addRoute('GET', $route, $handler, $options);
    }

    /**
     * Adds a POST route to the collection.
     *
     * This is simply an alias of $this->addRoute('POST', $route, $handler)
     *
     * @param string       $route
     * @param array|string $handler
     * @param array        $options
     */
    public function post(string $route, $handler, array $options = []) {
        $this->addRoute('POST', $route, $handler, $options);
    }

    /**
     * Adds a PUT route to the collection.
     *
     * This is simply an alias of $this->addRoute('PUT', $route, $handler)
     *
     * @param string       $route
     * @param array|string $handler
     * @param array        $options
     */
    public function put(string $route, $handler, array $options = []) {
        $this->addRoute('PUT', $route, $handler, $options);
    }

    /**
     * Adds a DELETE route to the collection.
     *
     * This is simply an alias of $this->addRoute('DELETE', $route, $handler)
     *
     * @param string       $route
     * @param array|string $handler
     * @param array        $options
     */
    public function delete(string $route, $handler, array $options = []) {
        $this->addRoute('DELETE', $route, $handler, $options);
    }

    /**
     * Adds a PATCH route to the collection.
     *
     * This is simply an alias of $this->addRoute('PATCH', $route, $handler)
     *
     * @param string       $route
     * @param array|string $handler
     * @param array        $options
     */
    public function patch(string $route, $handler, array $options = []) {
        $this->addRoute('PATCH', $route, $handler, $options);
    }

    /**
     * Adds a HEAD route to the collection.
     *
     * This is simply an alias of $this->addRoute('HEAD', $route, $handler)
     *
     * @param string       $route
     * @param array|string $handler
     * @param array        $options
     */
    public function head(string $route, $handler, array $options = []) {
        $this->addRoute('HEAD', $route, $handler, $options);
    }

    /**
     * @return array
     */
    public function getData(): array {
        return $this->dataGenerator->getData();
    }

    /**
     * @param array $origin
     * @param array $options
     * @return array
     */
    protected function mergeOptions(array $origin, array $options): array {
        return array_merge_recursive($origin, $options);
    }
}
