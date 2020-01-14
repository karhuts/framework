<?php
declare(strict_types=1);
namespace Karthus\Http\Message\Factory;

use Karthus\Http\Message\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseFactory
 *
 * @package Karthus\Http\Message\Factory
 */
class ResponseFactory implements ResponseFactoryInterface {
    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface {
        return new Response($code, $reasonPhrase);
    }

    /**
     * Create a new ResponseInterface.
     *
     * @param \Swoole\Http\Response $response
     * @return ResponseInterface
     */
    public function createResponseFromSwoole(\Swoole\Http\Response $resp): ResponseInterface {
        /** @var Response $response */
        $response = $this->createResponse();
        $response->withSwooleResponse($resp);
        return $response;
    }
}
