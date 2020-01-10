<?php
declare(strict_types=1);

namespace Karthus\Service;

use FastRoute\Dispatcher;
use Karthus\Contract\Able\Sendable;
use Karthus\Contract\ContainerInterface;
use Karthus\Contract\CoreMiddlewareInterface;
use Karthus\Contract\MiddlewareInitializerInterface;
use Karthus\Contract\OnRequestInterface;
use Karthus\Contract\ResponseInterface;
use Karthus\Dispatcher\HttpDispatcher;
use Karthus\Exception\ExceptionHandlerDispatcher;
use Karthus\Exception\HttpExceptionHandler;
use Karthus\Functions\CoreMiddleware;
use Karthus\Router\Dispatched;
use Karthus\Router\DispatcherFactory;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

use Karthus\Http\Server\Request as Psr7Request;
use Karthus\Http\Server\Response as Psr7Response;

use Throwable;

class Server implements OnRequestInterface, MiddlewareInitializerInterface {
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var HttpDispatcher
     */
    protected $dispatcher;
    /**
     * @var ExceptionHandlerDispatcher
     */
    protected $exceptionHandlerDispatcher;
    /**
     * @var array
     */
    protected $middlewares;
    /**
     * @var CoreMiddlewareInterface
     */
    protected $coreMiddleware;
    /**
     * @var array
     */
    protected $exceptionHandlers;
    /**
     * @var Dispatcher
     */
    protected $routerDispatcher;
    /**
     * @var string
     */
    protected $serverName;

    /**
     * Server constructor.
     *
     * @param ContainerInterface         $container
     * @param HttpDispatcher             $dispatcher
     * @param ExceptionHandlerDispatcher $exceptionHandlerDispatcher
     */
    public function __construct(ContainerInterface $container, HttpDispatcher $dispatcher, ExceptionHandlerDispatcher $exceptionHandlerDispatcher) {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->exceptionHandlerDispatcher = $exceptionHandlerDispatcher;
    }

    /**
     * 初始化中间件
     *
     * @param string $serverName
     */
    public function initCoreMiddleware(string $serverName): void {
        $this->serverName = $serverName;
        $this->coreMiddleware = $this->createCoreMiddleware();
        $this->routerDispatcher = $this->createDispatcher($serverName);
        $config = $this->container->get(ConfigInterface::class);
        $this->middlewares = $config->get('middlewares.' . $serverName, []);
        $this->exceptionHandlers = $config->get('exceptions.handler.' . $serverName, $this->getDefaultExceptionHandler());
    }

    /**
     * @param SwooleRequest  $request
     * @param SwooleResponse $response
     */
    public function onRequest(SwooleRequest $request, SwooleResponse $response): void {
        try {
            [$psr7Request, $psr7Response] = $this->initRequestAndResponse($request, $response);
            $psr7Request = $this->coreMiddleware->dispatch($psr7Request);
            /** @var Dispatched $dispatched */
            $dispatched = $psr7Request->getAttribute(Dispatched::class);
            $middlewares = $this->middlewares;
            if ($dispatched->isFound()) {
                $registedMiddlewares = MiddlewareManager::get($this->serverName, $dispatched->handler->route, $psr7Request->getMethod());
                $middlewares = array_merge($middlewares, $registedMiddlewares);
            }
            $psr7Response = $this->dispatcher->dispatch($psr7Request, $middlewares, $this->coreMiddleware);
        } catch (Throwable $throwable) {
            // Delegate the exception to exception handler.
            $psr7Response = $this->exceptionHandlerDispatcher->dispatch($throwable, $this->exceptionHandlers);
        } finally {
            // Send the Response to client.
            if (! isset($psr7Response) || ! $psr7Response instanceof Sendable) {
                return;
            }
            $psr7Response->send();
        }
    }

    /**
     * @return string
     */
    public function getServerName(): string {
        return $this->serverName;
    }

    /**
     * @param string $serverName
     * @return $this
     */
    public function setServerName(string $serverName) {
        $this->serverName = $serverName;
        return $this;
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
     * @return array
     */
    protected function getDefaultExceptionHandler(): array {
        return [
            HttpExceptionHandler::class,
        ];
    }

    /**
     * @return CoreMiddlewareInterface
     */
    protected function createCoreMiddleware(): CoreMiddlewareInterface {
        return make(CoreMiddleware::class, [$this->container, $this->serverName]);
    }

    /**
     * @param SwooleRequest  $request
     * @param SwooleResponse $response
     * @return array
     */
    protected function initRequestAndResponse(SwooleRequest $request, SwooleResponse $response): array {
        // Initialize PSR-7 Request and Response objects.
        Context::set(ServerRequestInterface::class, $psr7Request = Psr7Request::loadFromSwooleRequest($request));
        Context::set(ResponseInterface::class, $psr7Response = new Psr7Response($response));
        return [$psr7Request, $psr7Response];
    }
}
