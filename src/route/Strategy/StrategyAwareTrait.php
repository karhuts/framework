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

namespace karthus\route\Strategy;

trait StrategyAwareTrait
{
    /**
     * @var ?StrategyInterface
     */
    protected $strategy;

    public function setStrategy(StrategyInterface $strategy): StrategyAwareInterface
    {
        $this->strategy = $strategy;
        return $this;
    }

    public function getStrategy(): ?StrategyInterface
    {
        return $this->strategy;
    }
}
