<?php
use \Karthus\Service\ApplicationContext;
use \Swoole\Coroutine;
use \Swoole\Runtime;

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

/***
 * @param callable $callable
 */
function go(callable $callable):void {
    Coroutine::create($callable);
}

/**
 * @param callable $callable
 */
function co(callable $callable): void {
    Coroutine::create($callable);
}

/**
 * @param callable $callable
 */
function defer(callable $callable): void {
    Coroutine::defer($callable);
}

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


/**
 * @param $value
 * @return mixed
 */
function value($value) {
    return $value instanceof \Closure ? $value() : $value;
}
