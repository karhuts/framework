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

namespace karthus\route\Strategy;

use karthus\route\Http\Exception\MethodNotAllowedException;
use karthus\route\Http\Exception\NotFoundException;
use karthus\route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface StrategyInterface
{
    public function addResponseDecorator(callable $decorator): StrategyInterface;

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface;

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface;

    public function getThrowableHandler(): MiddlewareInterface;

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface;
}
