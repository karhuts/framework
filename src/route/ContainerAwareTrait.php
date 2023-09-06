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

use Psr\Container\ContainerInterface;
use RuntimeException;

trait ContainerAwareTrait
{
    /**
     * @var ?ContainerInterface
     */
    protected $container;

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container): ContainerAwareInterface
    {
        $this->container = $container;

        if ($this instanceof ContainerAwareInterface) {
            return $this;
        }

        throw new RuntimeException(sprintf(
            'Trait (%s) must be consumed by an instance of (%s)',
            __TRAIT__,
            ContainerAwareInterface::class
        ));
    }
}
