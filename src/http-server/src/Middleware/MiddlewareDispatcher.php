<?php
declare(strict_types=1);
namespace Karthus\Http\Server\Middleware;

use Karthus\Http\Server\TypeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class MiddlewareDispatcher
 *
 * @package Karthus\Http\Server\Middleware
 */
class MiddlewareDispatcher {
    /**
     * @var MiddlewareInterface[]
     */
    public $middleware;
    /**
     * @var ServerRequestInterface
     */
    public $request;
    /**
     * @var ResponseInterface
     */
    public $response;
    /**
     * MiddlewareDispatcher constructor.
     * @param array $middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(array $middleware, ServerRequestInterface $request, ResponseInterface $response) {
        $this->request  = $request;
        $this->response = $response;
        foreach ($middleware as $class) {
            $object = new $class(
                $request,
                $response
            );
            if (!($object instanceof MiddlewareInterface)) {
                throw new TypeException("{$class} type is not '" . MiddlewareInterface::class . "'");
            }
            $this->middleware[] = $object;
        }
    }
    /**
     * 调度
     * @return ResponseInterface
     */
    public function dispatch(): ResponseInterface
    {
        return (new RequestHandler($this->middleware, $this->response))->handle($this->request);
    }
}
