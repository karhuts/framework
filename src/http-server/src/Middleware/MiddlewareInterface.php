<?php
declare(strict_types=1);
namespace Karthus\Http\Server\Middleware;

/**
 * Interface MiddlewareInterface
 *
 * @package Karthus\Http\Server\Middleware
 */
interface MiddlewareInterface extends \Psr\Http\Server\MiddlewareInterface {
    /**
     * MiddlewareInterface constructor.
     * @param ServerRequest $request
     * @param Response $response
     */
    public function __construct(ServerRequest $request, Response $response);
}
