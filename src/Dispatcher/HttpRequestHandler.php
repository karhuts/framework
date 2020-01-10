<?php
declare(strict_types=1);

namespace Karthus\Dispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
class HttpRequestHandler extends AbstractRequestHandler implements RequestHandlerInterface {
    /**
     * Handles a request and produces a response.
     * May call other collaborating code to generate the response.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface {
        return $this->handleRequest($request);
    }
}
