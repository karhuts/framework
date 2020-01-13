<?php
declare(strict_types=1);

use Karthus\Exception\RuntimeException;
use Karthus\Functions\Arr;
use Karthus\Functions\Collection;
use Karthus\Service\ApplicationContext;
use Swoole\Coroutine;
use Swoole\Runtime;

if(!function_exists('call')){

    /**
     * Call a callback with the arguments.
     *
     * @param mixed $callback
     * @return null|mixed
     */
    function call($callback, array $args = []) {
        $result = null;
        if ($callback instanceof \Closure) {
            $result = $callback(...$args);
        } elseif (is_object($callback) || (is_string($callback) && function_exists($callback))) {
            $result = $callback(...$args);
        } elseif (is_array($callback)) {
            [$object, $method] = $callback;
            $result = is_object($object) ? $object->{$method}(...$args) : $object::$method(...$args);
        } else {
            $result = call_user_func_array($callback, $args);
        }
        return $result;
    }
}

if(!function_exists('make')){

    /**
     * Create a object instance, if the DI container exist in ApplicationContext,
     * then the object will be create by DI container via `make()` method, if not,
     * the object will create by `new` keyword.
     */
    function make(string $name, array $parameters = []) {
        if (ApplicationContext::hasContainer()) {
            $container = ApplicationContext::getContainer();
            if (method_exists($container, 'make')) {
                return $container->make($name, $parameters);
            }
        }
        $parameters = array_values($parameters);
        return new $name(...$parameters);
    }
}


if(!function_exists('go')){

    /***
     * @param callable $callable
     */
    function go(callable $callable):void {
        Coroutine::create($callable);
    }
}

if(!function_exists('co')){

    /**
     * @param callable $callable
     */
    function co(callable $callable): void {
        Coroutine::create($callable);
    }
}

if(!function_exists('defer')){

    /**
     * @param callable $callable
     */
    function defer(callable $callable): void {
        Coroutine::defer($callable);
    }
}


if(!function_exists('run')){
    /**
     * Run callable in non-coroutine environment, all hook functions by Swoole only available in the callable.
     *
     * @param callable $callback
     * @param int      $flags
     * @return bool
     */
    function run(callable $callback, int $flags = SWOOLE_HOOK_ALL): bool {
        if (Coroutine::inCoroutine()) {
            throw new RuntimeException('Function \'run\' only execute in non-coroutine environment.');
        }
        Runtime::enableCoroutine(true, $flags);
        $result = Coroutine\Run($callback);
        Runtime::enableCoroutine(false);
        return $result;
    }
}

if(!function_exists('value')){

    /**
     * 一个匿名函数
     *
     * @param $value
     * @return mixed
     */
    function value($value) {
        return $value instanceof \Closure ? $value() : $value;
    }
}


if(!function_exists('data_get')){

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param array|int|string $key
     * @param null|mixed       $default
     * @param mixed            $target
     * @return array|mixed
     */
    function data_get($target, $key, $default = null) {
        if (is_null($key)) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string) $key : $key);
        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
                    return value($default);
                }
                $result = [];
                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }
                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }
        return $target;
    }
}

if(!function_exists('collect')){
    /**
     * @param null $value
     * @return Collection
     */
    function collect($value = null) {
        return new Collection($value);
    }
}
