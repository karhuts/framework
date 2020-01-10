<?php
declare(strict_types=1);
namespace Karthus\Contract;

interface FrequencyInterface {
    /**
     * @param int $number
     * @return bool
     */
    public function hit(int $number = 1): bool;

    /**
     * @return float
     */
    public function frequency(): float;
}
