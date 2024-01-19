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

namespace karthus\route\Middleware;

use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareAwareTrait
{
    protected array $middleware = [];

    public function getMiddlewareStack(): iterable
    {
        return $this->middleware;
    }

    public function lazyMiddleware(string $middleware): MiddlewareAwareInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function lazyMiddlewares(array $middlewares): MiddlewareAwareInterface
    {
        foreach ($middlewares as $middleware) {
            $this->lazyMiddleware($middleware);
        }

        return $this;
    }

    public function lazyPrependMiddleware(string $middleware): MiddlewareAwareInterface
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    public function middleware(MiddlewareInterface $middleware): MiddlewareAwareInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function middlewares(array $middlewares): MiddlewareAwareInterface
    {
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }

        return $this;
    }

    public function prependMiddleware(MiddlewareInterface $middleware): MiddlewareAwareInterface
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    public function shiftMiddleware(): MiddlewareInterface
    {
        $middleware = array_shift($this->middleware);

        if ($middleware === null) {
            throw new OutOfBoundsException('Reached end of middleware stack. Does your controller return a response?');
        }

        return $middleware;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolveMiddleware(mixed $middleware, ?ContainerInterface $container = null): MiddlewareInterface
    {
        if ($container === null && is_string($middleware) && class_exists($middleware)) {
            $middleware = new $middleware();
        }

        if ($container !== null && is_string($middleware) && $container->has($middleware)) {
            $middleware = $container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new InvalidArgumentException(sprintf('Could not resolve middleware class: %s', $middleware));
    }
}
