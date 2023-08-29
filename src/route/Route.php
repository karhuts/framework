<?php
declare(strict_types=1);

namespace karthus\route;

use karthus\route\Middleware\MiddlewareAwareInterface;
use karthus\route\Middleware\MiddlewareAwareTrait;
use karthus\route\Strategy\StrategyAwareInterface;
use karthus\route\Strategy\StrategyAwareTrait;
use karthus\route\Strategy\StrategyInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use function array_merge;
use function count;
use function preg_replace_callback;
use function str_replace;

class Route implements
    StrategyAwareInterface,
    MiddlewareInterface,
    MiddlewareAwareInterface
{
    use StrategyAwareTrait;
    use MiddlewareAwareTrait;

    /**
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * @var array
     */
    protected array $methods = [];

    /**
     * @var string
     */
    protected string $path = '';

    /**
     * @var callable|string
     */
    protected $callback = null;

    /**
     * @var array
     */
    protected array $middlewares = [];

    /**
     * @var array
     */
    protected array $params = [];

    /**
     * Router constructor.
     * @param array $methods
     * @param string $path
     * @param callable|array $callback
     */
    public function __construct(array $methods, string $path, callable|array $callback)
    {
        $this->methods = $methods;
        $this->path = $path;
        $this->callback = $callback;
    }

    /**
     * Get name.
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }


    /**
     * Middleware.
     * @param mixed|null $middleware
     * @return Route
     */
    public function middleware(mixed $middleware = null): Route
    {
        if ($middleware === null) {
            return $this;
        }
        $this->middlewares = array_merge($this->middlewares, is_array($middleware) ? $middleware : [$middleware]);
        return $this;
    }

    /**
     * GetPath.
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * GetMethods.
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * GetCallback.
     * @return callable|null
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * GetMiddleware.
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middlewares;
    }

    /**
     * Param.
     * @param string|null $name
     * @param $default
     * @return array|mixed|null
     */
    public function param(string $name = null, $default = null): mixed
    {
        if ($name === null) {
            return $this->params;
        }
        return $this->params[$name] ?? $default;
    }

    /**
     * @param ContainerInterface|null $container
     * @return callable
     */
    public function getCallable(?ContainerInterface $container = null): callable
    {
        $callable = $this->callback;


        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $callable = [$this->resolve($callable[0], $container), $callable[1]];
        }

        if (is_string($callable)) {
            $callable = $this->resolve($callable, $container);
        }

        if (!is_callable($callable)) {
            throw new RuntimeException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    /**
     * SetParams.
     * @param array $params
     * @return $this
     */
    public function setParams(array $params): Route
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * Url.
     * @param array $parameters
     * @return string
     */
    public function url(array $parameters = []): string
    {
        if (empty($parameters)) {
            return $this->path;
        }
        $path = str_replace(['[', ']'], '', $this->path);
        $path = preg_replace_callback('/\{(.*?)(?:\:[^\}]*?)*?\}/', function ($matches) use (&$parameters) {
            if (!$parameters) {
                return $matches[0];
            }
            if (isset($parameters[$matches[1]])) {
                $value = $parameters[$matches[1]];
                unset($parameters[$matches[1]]);
                return $value;
            }
            $key = key($parameters);
            if (is_int($key)) {
                $value = $parameters[$key];
                unset($parameters[$key]);
                return $value;
            }
            return $matches[0];
        }, $path);
        return count($parameters) > 0 ? $path . '?' . http_build_query($parameters) : $path;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $strategy = $this->getStrategy();

        if (!($strategy instanceof StrategyInterface)) {
            throw new RuntimeException('A strategy must be set to process a route');
        }

        return $strategy->invokeRouteCallable($this, $request);
    }

    /**
     * @param string $class
     * @param ContainerInterface|null $container
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolve(string $class, ?ContainerInterface $container = null): mixed
    {
        if ($container instanceof ContainerInterface && $container->has($class)) {
            return $container->get($class);
        }

        if (class_exists($class)) {
            return new $class();
        }

        return $class;
    }
}
