<?php
declare(strict_types=1);
namespace Karthus\Http\Middleware;

use Karthus\Http\Message\Response;
use Karthus\Http\Message\ServerRequest;
use Karthus\Http\Server\Middleware\MiddlewareInterface;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GlobalMiddleware implements MiddlewareInterface {
    /**
     * @var ServerRequest
     */
    public $request;
    /**
     * @var Response
     */
    public $response;
    /**
     * GlobalMiddleware constructor.
     * @param ServerRequest $request
     * @param Response $response
     */
    public function __construct(ServerRequest $request, Response $response) {
        $this->request  = $request;
        $this->response = $response;
    }
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        return $handler->handle($request);
    }
}
