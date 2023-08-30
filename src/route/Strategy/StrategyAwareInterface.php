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

interface StrategyAwareInterface
{
    public function getStrategy(): ?StrategyInterface;

    public function setStrategy(StrategyInterface $strategy): StrategyAwareInterface;
}
