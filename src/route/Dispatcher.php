<?php
declare(strict_types=1);
namespace karthus\route;

use karthus\route\Http\Exception\MethodNotAllowedException;
use karthus\route\Http\Exception\NotFoundException;
use karthus\route\Strategy\ApplicationStrategy;
use karthus\route\Strategy\StrategyAwareInterface;
use karthus\route\Strategy\StrategyAwareTrait;
use karthus\route\Strategy\StrategyInterface;
use karthus\route\Middleware\{MiddlewareAwareTrait, MiddlewareAwareInterface};
use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Dispatcher implements
    RequestHandlerInterface,
    MiddlewareAwareInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use StrategyAwareTrait;

    public function dispatchRequest(GroupCountBased $router, ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        // 我解析一下 $uri
        $uri    = $request->getUri()->getPath();
        $match  = $router->dispatch($method, $uri);
        switch ($match[0]) {
            case FastRoute::NOT_FOUND:
                $this->setNotFoundDecoratorMiddleware();
                break;
            case FastRoute::METHOD_NOT_ALLOWED:
                $allowed = (array) $match[1];
                $this->setMethodNotAllowedDecoratorMiddleware($allowed);
                break;
            case FastRoute::FOUND:;
                /** @var Route $route */
                $route = $match[1]['route'];
                $args = !empty($match[2]) ? $match[2] : null;
                if ($args) {
                    $route->setParams($args);
                }
                $this->setFoundMiddleware($route);
                $request = $this->requestWithRouteAttributes($request, $route);
                break;
        }

        return $this->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->shiftMiddleware();
        return $middleware->process($request, $this);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Route $route
     * @return ServerRequestInterface
     */
    protected function requestWithRouteAttributes(ServerRequestInterface $request, Route $route): ServerRequestInterface
    {

        $routerParams = $route->getParams();
        foreach ($routerParams as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $request;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setFoundMiddleware(Route $router): void
    {
        if ($router->getStrategy() === null) {
            $strategy = new ApplicationStrategy();
            $router->setStrategy($strategy);
        }

        $strategy = $router->getStrategy();

        $container = $strategy instanceof ContainerAwareInterface ? $strategy->getContainer() : null;
        // wrap entire dispatch process in exception handler
        $this->prependMiddleware($strategy->getThrowableHandler());
        foreach ($router->getMiddleware() as $middleware) {
            $this->middleware($this->resolveMiddleware($middleware, $container));
        }
        $this->middleware($router);
    }

    /**
     * @param array $allowed
     * @return void
     */
    protected function setMethodNotAllowedDecoratorMiddleware(array $allowed): void
    {
        $strategy = $this->getStrategy();

        if (!($strategy instanceof StrategyInterface)) {
            throw new RuntimeException('Cannot determine strategy to use for dispatch of method not allowed route');
        }

        $middleware = $strategy->getMethodNotAllowedDecorator(new MethodNotAllowedException($allowed));
        $this->prependMiddleware($middleware);
    }

    /**
     * @return void
     */
    protected function setNotFoundDecoratorMiddleware(): void
    {
        if ($this->getStrategy() === null) {
            $strategy = new ApplicationStrategy();
            $this->setStrategy($strategy);
        }
        $strategy = $this->getStrategy();

        if (!($strategy instanceof StrategyInterface)) {
            throw new RuntimeException('Cannot determine strategy to use for dispatch of not found route');
        }

        $middleware = $strategy->getNotFoundDecorator(new NotFoundException());
        $this->prependMiddleware($middleware);
    }
}