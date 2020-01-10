<?php
declare(strict_types=1);

namespace Karthus\Functions;

use Karthus\Traits\StaticInstance;

class Propagation {
    use StaticInstance;
    /**
     * Determine if the exception should propagate to next handler.
     *
     * @var bool
     */
    protected $propagationStopped = false;

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool {
        return $this->propagationStopped;
    }

    /**
     * @param bool $propagationStopped
     * @return Propagation
     */
    public function setPropagationStopped(bool $propagationStopped): Propagation {
        $this->propagationStopped = $propagationStopped;
        return $this;
    }
}
