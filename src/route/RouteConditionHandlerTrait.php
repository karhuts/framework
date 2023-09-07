<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route;

use Psr\Http\Message\ServerRequestInterface;

trait RouteConditionHandlerTrait
{
    /**
     * @var ?string
     */
    protected $domain;

    /**
     * @var ?string
     */
    protected $name;

    /**
     * @var ?int
     */
    protected $port;

    /**
     * @var ?string
     */
    protected $scheme;

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function setDomain(string $domain): RouteConditionHandlerInterface
    {
        $this->domain = $domain;
        return $this;
    }

    public function setName(string $name): RouteConditionHandlerInterface
    {
        $this->name = $name;
        return $this;
    }

    public function setPort(int $port): RouteConditionHandlerInterface
    {
        $this->port = $port;
        return $this;
    }

    public function setScheme(string $scheme): RouteConditionHandlerInterface
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @param Route $route
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isExtraConditionMatch(Route $route, ServerRequestInterface $request): bool
    {
        $path = $route->getPath();
        // check for scheme condition
        $scheme = $route->getScheme();
        if ($scheme !== null && $scheme !== $request->getUri()->getScheme()) {
            return false;
        }

        // check for domain condition
        $host = $route->getDomain();
        if ($host !== null && $host !== $request->getUri()->getHost()) {
            return false;
        }

        // check for port condition
        $port = $route->getPort();
        if ($port !== null && $port !== $request->getUri()->getPort()) {
            return false;
        }

        return true;
    }
}
