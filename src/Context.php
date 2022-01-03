<?php
declare(strict_types=1);

namespace Karthus;

use Swoole\Coroutine;

class Context {
    /**
     * @var array
     */
    protected static $nonCoContext = [];

    /**
     * @param string $id
     * @param $value
     * @return mixed
     */
    public static function set(string $id, $value) {
        if (self::inCoroutine()) {
            Coroutine::getContext()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }
        return $value;
    }

    /**
     * @param string $id
     * @param $default
     * @param $coroutineId
     * @return mixed|null
     */
    public static function get(string $id, $default = null, $coroutineId = null) {
        if (self::inCoroutine()) {
            if ($coroutineId !== null) {
                return Coroutine::getContext($coroutineId)[$id] ?? $default;
            }
            return Coroutine::getContext()[$id] ?? $default;
        }

        return static::$nonCoContext[$id] ?? $default;
    }

    /**
     * @param string $id
     * @param $coroutineId
     * @return bool
     */
    public static function has(string $id, $coroutineId = null): bool {
        if (self::inCoroutine()) {
            if ($coroutineId !== null) {
                return isset(Coroutine::getContext($coroutineId)[$id]);
            }
            return isset(Coroutine::getContext()[$id]);
        }

        return isset(static::$nonCoContext[$id]);
    }

    /**
     * Release the context when you are not in coroutine environment.
     */
    public static function destroy(string $id): void {
        unset(static::$nonCoContext[$id]);
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
     * @return bool
     */
    public static function inCoroutine(): bool {
        return Coroutine::getCid() > 0;
    }
}