<?php
declare(strict_types=1);

namespace karthus\route;
use karthus\route\Middleware\MiddlewareAwareInterface;
use karthus\route\Middleware\MiddlewareAwareTrait;
use karthus\route\Strategy\StrategyAwareInterface;
use karthus\route\Strategy\StrategyAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteGroup implements
    StrategyAwareInterface,
    MiddlewareInterface,
    MiddlewareAwareInterface
{
    use StrategyAwareTrait;
    use MiddlewareAwareTrait;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var RouteCollectionInterface
     */
    protected $collection;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @param string $prefix
     * @param callable $callback
     * @param RouteCollectionInterface $collection
     */
    public function __construct(string $prefix, callable $callback, RouteCollectionInterface $collection)
    {
        $this->callback   = $callback;
        $this->collection = $collection;
        $this->prefix     = sprintf('/%s', ltrim($prefix, '/'));
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }

    public function __invoke(): void
    {
        ($this->callback)($this);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
