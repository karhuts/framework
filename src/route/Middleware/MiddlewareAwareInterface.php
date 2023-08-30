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

namespace karthus\route\Middleware;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareAwareInterface
{
    public function getMiddlewareStack(): iterable;

    public function lazyMiddleware(string $middleware): MiddlewareAwareInterface;

    public function lazyMiddlewares(array $middlewares): MiddlewareAwareInterface;

    public function lazyPrependMiddleware(string $middleware): MiddlewareAwareInterface;

    public function middleware(MiddlewareInterface $middleware): MiddlewareAwareInterface;

    public function middlewares(array $middlewares): MiddlewareAwareInterface;

    public function prependMiddleware(MiddlewareInterface $middleware): MiddlewareAwareInterface;

    public function shiftMiddleware(): MiddlewareInterface;
}
