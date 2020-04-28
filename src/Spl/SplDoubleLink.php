<?php
declare(strict_types=1);

namespace Karthus\Spl;

/**
 * Class SplDoubleLink
 *
 * @package Karthus\Spl
 */
class SplDoubleLink {
    private $next;
    private $pre;

    /**
     * @return bool
     */
    public function hashNext(): bool {
        return (bool) $this->next;
    }

    /**
     * @return bool
     */
    public function hashPre(): bool {
        return (bool) $this->pre;
    }

    /**
     * @param mixed ...$arg
     * @return object
     */
    public function next(...$arg): object {
        if (!$this->next) {
            $this->next = $this->newInstance(...$arg);
        }
        return $this->next;
    }

    /**
     * @param mixed ...$arg
     * @return object
     */
    public function pre(...$arg): object {
        if (!$this->pre) {
            $this->pre = $this->newInstance(...$arg);
        }
        return $this->pre;
    }

    /**
     * @return $this
     */
    public function delPre() {
        $this->pre = null;
        return $this;
    }

    /**
     * @return $this
     */
    public function delNext() {
        $this->next = null;
        return $this;
    }

    /**
     * @param mixed ...$arg
     * @return object
     */
    private function newInstance(...$arg): object {
        $ref = new \ReflectionClass(static::class);
        return $ref->newInstanceArgs($arg);
    }
}
