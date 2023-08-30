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

namespace karthus\route;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    public function getContainer(): ?ContainerInterface;

    public function setContainer(ContainerInterface $container): ContainerAwareInterface;
}
