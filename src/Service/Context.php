<?php
declare(strict_types=1);

namespace Karthus\Service;

use Swoole\Coroutine as SwCoroutine;

class Context {

    /**
     * @var array
     */
    protected static $nonCoContext = [];

    /**
     * @param string $id
     * @param        $value
     * @return mixed
     */
    public static function set(string $id, $value) {
        if (Coroutine::inCoroutine()) {
            SwCoroutine::getContext()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }
        return $value;
    }

    /**
     * @param string $id
     * @param null   $default
     * @param null   $coroutineId
     * @return mixed|null
     */
    public static function get(string $id, $default = null, $coroutineId = null) {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return SwCoroutine::getContext($coroutineId)[$id] ?? $default;
            }
            return SwCoroutine::getContext()[$id] ?? $default;
        }
        return static::$nonCoContext[$id] ?? $default;
    }

    /**
     * @param string $id
     * @param null   $coroutineId
     * @return bool
     */
    public static function has(string $id, $coroutineId = null) {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return isset(SwCoroutine::getContext($coroutineId)[$id]);
            }
            return isset(SwCoroutine::getContext()[$id]);
        }
        return isset(static::$nonCoContext[$id]);
    }


    /**
     * Release the context when you are not in coroutine environment.
     */
    public static function destroy(string $id) {
        unset(static::$nonCoContext[$id]);
    }


    /**
     * Copy the context from a coroutine to current coroutine.
     */
    public static function copy(int $fromCoroutineId, array $keys = []): void {
        /**
         * @var \ArrayObject
         * @var \ArrayObject $current
         */
        $from = SwCoroutine::getContext($fromCoroutineId);
        $current = SwCoroutine::getContext();
        $current->exchangeArray($keys ? array_fill_keys($keys, $from->getArrayCopy()) : $from->getArrayCopy());
    }


    /**
     * Retrieve the value and override it by closure.
     */
    public static function override(string $id, \Closure $closure) {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);
        return $value;
    }


    /**
     * Retrieve the value and store it if not exists.
     *
     * @param string $id
     * @param mixed  $value
     * @return mixed|null
     */
    public static function getOrSet(string $id, $value) {
        if (! self::has($id)) {
            return self::set($id, value($value));
        }
        return self::get($id);
    }

    /**
     * @return array|mixed
     */
    public static function getContainer() {
        if (Coroutine::inCoroutine()) {
            return SwCoroutine::getContext();
        }
        return static::$nonCoContext;
    }
}
