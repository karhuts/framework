<?php
declare(strict_types=1);

namespace Karthus\Functions;

use Closure;
use FastRoute\Dispatcher;
use Karthus\Contract\Able\Arrayable;
use Karthus\Contract\Able\Jsonable;
use Karthus\Contract\ContainerInterface;
use Karthus\Contract\CoreMiddlewareInterface;
use Karthus\Contract\MethodDefinitionCollectorInterface;
use Karthus\Contract\NormalizerInterface;
use Karthus\Exception\ServerException;
use Karthus\Router\Dispatched;
use Karthus\Router\DispatcherFactory;
use Karthus\Service\Context;
use Karthus\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CoreMiddleware implements CoreMiddlewareInterface {
    /**
     * @var Dispatcher
     */
    protected $dispatcher;
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var MethodDefinitionCollectorInterface
     */
    private $methodDefinitionCollector;
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * CoreMiddleware constructor.
     *
     * @param ContainerInterface $container
     * @param string             $serverName
     */
    public function __construct(ContainerInterface $container, string $serverName) {
        $this->container = $container;
        $this->dispatcher = $this->createDispatcher($serverName);
        $this->normalizer = $this->container->get(NormalizerInterface::class);
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function dispatch(ServerRequestInterface $request): ServerRequestInterface {
        $routes = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        $dispatched = new Dispatched($routes);
        return Context::set(ServerRequestInterface::class, $request->withAttribute(Dispatched::class, $dispatched));
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $request = Context::set(ServerRequestInterface::class, $request);
        /** @var Dispatched $dispatched */
        $dispatched = $request->getAttribute(Dispatched::class);
        if (! $dispatched instanceof Dispatched) {
            throw new ServerException(sprintf('The dispatched object is not a %s object.', Dispatched::class));
        }
        switch ($dispatched->status) {
            case Dispatcher::NOT_FOUND:
                $response = $this->handleNotFound($request);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response = $this->handleMethodNotAllowed($dispatched->params, $request);
                break;
            case Dispatcher::FOUND:
                $response = $this->handleFound($dispatched, $request);
                break;
        }
        if (! $response instanceof ResponseInterface) {
            $response = $this->transferToResponse($response, $request);
        }
        return $response->withAddedHeader('Server', 'Hyperf');
    }

    /**
     * @return MethodDefinitionCollectorInterface
     */
    public function getMethodDefinitionCollector(): MethodDefinitionCollectorInterface {
        return $this->methodDefinitionCollector;
    }

    /**
     * @return NormalizerInterface
     */
    public function getNormalizer(): NormalizerInterface {
        return $this->normalizer;
    }

    /**
     * @param string $serverName
     * @return Dispatcher
     */
    protected function createDispatcher(string $serverName): Dispatcher {
        $factory = $this->container->get(DispatcherFactory::class);
        return $factory->getDispatcher($serverName);
    }
    /**
     * Handle the response when found.
     *
     * @return array|Arrayable|mixed|ResponseInterface|string
     */
    protected function handleFound(Dispatched $dispatched, ServerRequestInterface $request) {
        if ($dispatched->handler->callback instanceof Closure) {
            $response = call($dispatched->handler->callback);
        } else {
            [$controller, $action] = $this->prepareHandler($dispatched->handler->callback);
            $controllerInstance = $this->container->get($controller);
            if (! method_exists($controller, $action)) {
                // Route found, but the handler does not exist.
                return $this->response()->withStatus(500)->withBody(new SwooleStream('Method of class does not exist.'));
            }
            $parameters = $this->parseParameters($controller, $action, $dispatched->params);
            $response = $controllerInstance->{$action}(...$parameters);
        }
        return $response;
    }

    /**
     * Handle the response when cannot found any routes.
     *
     * @return array|Arrayable|mixed|ResponseInterface|string
     */
    protected function handleNotFound(ServerRequestInterface $request) {
        return $this->response()->withStatus(404);
    }
    /**
     * Handle the response when the routes found but doesn't match any available methods.
     *
     * @return array|Arrayable|mixed|ResponseInterface|string
     */
    protected function handleMethodNotAllowed(array $methods, ServerRequestInterface $request) {
        return $this->response()->withStatus(405)->withAddedHeader('Allow', implode(', ', $methods));
    }

    /**
     * @param array|string $handler
     * @return array
     */
    protected function prepareHandler($handler): array {
        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new \RuntimeException('Handler not exist.');
    }

    /**
     * Transfer the non-standard response content to a standard response object.
     *
     * @param array|Arrayable|Jsonable|string $response
     * @return ResponseInterface
     */
    protected function transferToResponse($response, ServerRequestInterface $request): ResponseInterface {
        if (is_string($response)) {
            return $this->response()->withAddedHeader('content-type', 'text/plain')->withBody(new SwooleStream($response));
        }
        if (is_array($response) || $response instanceof Arrayable) {
            if ($response instanceof Arrayable) {
                $response = $response->toArray();
            }
            return $this->response()
                ->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream(json_encode($response, JSON_UNESCAPED_UNICODE)));
        }
        if ($response instanceof Jsonable) {
            return $this->response()
                ->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream((string) $response));
        }
        return $this->response()->withAddedHeader('content-type', 'text/plain')->withBody(new SwooleStream((string) $response));
    }
    /**
     * Get response instance from context.
     */
    protected function response(): ResponseInterface {
        return Context::get(ResponseInterface::class);
    }

    /**
     * Parse the parameters of method definitions, and then bind the specified arguments or
     * get the value from DI container, combine to a argument array that should be injected
     * and return the array.
     *
     * @param string $controller
     * @param string $action
     * @param array  $arguments
     * @return array
     */
    protected function parseParameters(string $controller, string $action, array $arguments): array {
        $injections = [];
        $definitions = $this->getMethodDefinitionCollector()->getParameters($controller, $action);
        foreach ($definitions ?? [] as $pos => $definition) {
            $value = $arguments[$pos] ?? $arguments[$definition->getMeta('name')] ?? null;
            if ($value === null) {
                if ($definition->getMeta('defaultValueAvailable')) {
                    $injections[] = $definition->getMeta('defaultValue');
                } elseif ($definition->allowsNull()) {
                    $injections[] = null;
                } elseif ($this->container->has($definition->getName())) {
                    $injections[] = $this->container->get($definition->getName());
                } else {
                    throw new \InvalidArgumentException("Parameter '{$definition->getMeta('name')}' "
                        . "of {$controller}::{$action} should not be null");
                }
            } else {
                $injections[] = $this->getNormalizer()->denormalize($value, $definition->getName());
            }
        }
        return $injections;
    }
}
