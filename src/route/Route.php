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

use karthus\route\Middleware\MiddlewareAwareInterface;
use karthus\route\Middleware\MiddlewareAwareTrait;
use karthus\route\Strategy\StrategyAwareInterface;
use karthus\route\Strategy\StrategyAwareTrait;
use karthus\route\Strategy\StrategyInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

use function array_merge;
use function count;
use function preg_replace_callback;
use function str_replace;

class Route implements StrategyAwareInterface, MiddlewareInterface, MiddlewareAwareInterface, RouteConditionHandlerInterface
{
    use StrategyAwareTrait;
    use MiddlewareAwareTrait;
    use RouteConditionHandlerTrait;

    protected array $methods = [];

    protected string $path = '';

    /**
     * @var callable|string
     */
    protected $callback;

    protected array $middlewares = [];

    protected array $permissions = [];

    protected array $params = [];

    protected bool $group = false;

    /**
     * Router constructor.
     */
    public function __construct(
        array $methods,
        string $path,
        array|callable $callback,
        bool $group = false
    ) {
        $this->methods = $methods;
        $this->path = $path;
        $this->callback = $callback;
        $this->group = (bool) $group;
    }

    public function isGroup(): bool
    {
        return (bool) $this->group;
    }

    /**
     * Middleware.
     */
    public function middleware(mixed $middleware = null): Route
    {
        if ($middleware === null) {
            return $this;
        }
        $this->middlewares = array_merge($this->middlewares, is_array($middleware) ? $middleware : [$middleware]);
        return $this;
    }

    /**
     * @return $this
     */
    public function permissions(array $permission): Route
    {
        if (empty($permission)) {
            return $this;
        }
        $this->permissions = array_merge($this->permissions, $permission);
        return $this;
    }

    /**
     * GetPath.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * GetMethods.
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getCallback(): array|callable
    {
        return $this->callback;
    }

    /**
     * GetMiddleware.
     */
    public function getMiddleware(): array
    {
        return $this->middlewares;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Param.
     * @return null|array|mixed
     */
    public function param(string $name = null, mixed $default = null): mixed
    {
        if ($name === null) {
            return $this->params;
        }
        return $this->params[$name] ?? $default;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCallable(?ContainerInterface $container = null): callable
    {
        $callable = $this->callback;

        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $callable = [$this->resolve($callable[0], $container), $callable[1]];
        }

        if (is_string($callable)) {
            $callable = $this->resolve($callable, $container);
        }

        if (! is_callable($callable)) {
            throw new RuntimeException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    /**
     * SetParams.
     * @return $this
     */
    public function setParams(array $params): Route
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Url.
     */
    public function url(array $parameters = []): string
    {
        if (empty($parameters)) {
            return $this->path;
        }
        $path = str_replace(['[', ']'], '', $this->path);
        $path = preg_replace_callback('/\{(.*?)(?:\:[^\}]*?)*?\}/', function ($matches) use (&$parameters) {
            if (! $parameters) {
                return $matches[0];
            }
            if (isset($parameters[$matches[1]])) {
                $value = $parameters[$matches[1]];
                unset($parameters[$matches[1]]);
                return $value;
            }
            $key = key($parameters);
            if (is_int($key)) {
                $value = $parameters[$key];
                unset($parameters[$key]);
                return $value;
            }
            return $matches[0];
        }, $path);
        return count($parameters) > 0 ? $path . '?' . http_build_query($parameters) : $path;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $strategy = $this->getStrategy();

        if (! $strategy instanceof StrategyInterface) {
            throw new RuntimeException('A strategy must be set to process a route');
        }

        return $strategy->invokeRouteCallable($this, $request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolve(string $class, ?ContainerInterface $container = null): mixed
    {
        if ($container instanceof ContainerInterface && $container->has($class)) {
            return $container->get($class);
        }

        if (class_exists($class)) {
            return new $class();
        }

        return $class;
    }
}
