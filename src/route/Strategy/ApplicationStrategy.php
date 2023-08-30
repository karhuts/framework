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

namespace karthus\route\Strategy;

use karthus\route\ContainerAwareInterface;
use karthus\route\ContainerAwareTrait;
use karthus\route\Http\Exception\MethodNotAllowedException;
use karthus\route\Http\Exception\NotFoundException;
use karthus\route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ApplicationStrategy extends AbstractStrategy implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                try {
                    return $handler->handle($request);
                } catch (Throwable $e) {
                    throw $e;
                }
            }
        };
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());
        $response = $controller($request, $route->getParams());
        return $this->decorateResponse($response);
    }

    protected function throwThrowableMiddleware(Throwable $error): MiddlewareInterface
    {
        return new class($error) implements MiddlewareInterface {
            protected $error;

            public function __construct(Throwable $error)
            {
                $this->error = $error;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                throw $this->error;
            }
        };
    }
}
