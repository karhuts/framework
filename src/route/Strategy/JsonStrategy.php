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

use JsonSerializable;
use karthus\route\ContainerAwareInterface;
use karthus\route\ContainerAwareTrait;
use karthus\route\Http\Exception\Exception;
use karthus\route\Http\Exception\MethodNotAllowedException;
use karthus\route\Http\Exception\NotFoundException;
use karthus\route\Route;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class JsonStrategy extends AbstractStrategy implements ContainerAwareInterface, OptionsHandlerInterface
{
    use ContainerAwareTrait;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var int
     */
    protected $jsonFlags;

    public function __construct(ResponseFactoryInterface $responseFactory, int $jsonFlags = 0)
    {
        $this->responseFactory = $responseFactory;
        $this->jsonFlags = $jsonFlags;

        $this->addResponseDecorator(static function (ResponseInterface $response): ResponseInterface {
            if ($response->hasHeader('content-type') === false) {
                $response = $response->withHeader('content-type', 'application/json');
            }

            return $response;
        });
    }

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    public function getOptionsCallable(array $methods): callable
    {
        return function () use ($methods): ResponseInterface {
            $options = implode(', ', $methods);
            $response = $this->responseFactory->createResponse();
            $response = $response->withHeader('allow', $options);
            return $response->withHeader('access-control-allow-methods', $options);
        };
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class($this->responseFactory->createResponse()) implements MiddlewareInterface {
            protected $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                try {
                    return $handler->handle($request);
                } catch (Throwable $exception) {
                    $response = $this->response;

                    if ($exception instanceof Exception) {
                        return $exception->buildJsonResponse($response);
                    }

                    $response->getBody()->write(json_encode([
                        'status_code' => 500,
                        'reason_phrase' => $exception->getMessage(),
                    ]));

                    $response = $response->withAddedHeader('content-type', 'application/json');
                    return $response->withStatus(500, strtok($exception->getMessage(), "\n"));
                }
            }
        };
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());
        $response = $controller($request, $route->getParams());

        if ($this->isJsonSerializable($response)) {
            $body = json_encode($response, $this->jsonFlags);
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($body);
        }

        return $this->decorateResponse($response);
    }

    protected function buildJsonResponseMiddleware(Exception $exception): MiddlewareInterface
    {
        return new class($this->responseFactory->createResponse(), $exception) implements MiddlewareInterface {
            protected $response;

            protected $exception;

            public function __construct(ResponseInterface $response, Exception $exception)
            {
                $this->response = $response;
                $this->exception = $exception;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->exception->buildJsonResponse($this->response);
            }
        };
    }

    protected function isJsonSerializable($response): bool
    {
        if ($response instanceof ResponseInterface) {
            return false;
        }

        return is_array($response) || is_object($response) || $response instanceof JsonSerializable;
    }
}
