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

interface RouteConditionHandlerInterface
{
    public function getDomain(): ?string;

    public function getName(): ?string;

    public function getPort(): ?int;

    public function getScheme(): ?string;

    public function setDomain(string $host): RouteConditionHandlerInterface;

    public function setName(string $name): RouteConditionHandlerInterface;

    public function setPort(int $port): RouteConditionHandlerInterface;

    public function setScheme(string $scheme): RouteConditionHandlerInterface;
}
