<?php
declare(strict_types=1);

use Karthus\Console\Application;
use Karthus\Coroutine\Coroutine;
use Karthus\Injector\ApplicationContext;

if(!function_exists('call')){
    /**
     * Call a callback with the arguments.
     *
     * @param mixed $callback
     * @param array $args
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


if (!function_exists('println')) {
    /**
     * 输出字符串并换行
     * @param $strings
     */
    function println($strings) {
        echo $strings . PHP_EOL;
    }
}


if (!function_exists('go')) {
    /**
     * 创建协程
     * @param       $function
     * @param mixed ...$params
     */
    function go($function, ...$params) {
        Coroutine::create($function, ...$params);
    }
}

if (!function_exists('co')) {
    /**
     * 创建协程
     * @param       $function
     * @param mixed ...$params
     */
    function co($function, ...$params) {
        Coroutine::create($function, ...$params);
    }
}

if (!function_exists('defer')) {
    /**
     * 创建延迟执行
     *
     * @param callable $callable
     */
    function defer(callable $callable) {
        Coroutine::defer($callable);
    }
}

if (!function_exists('context')) {
    /**
     * 获取全局上下文对象
     * @return ApplicationContext
     */
    function context() {
        return app()->context;
    }
}


if (!function_exists('app')) {
    /**
     * 获取全局App对象
     *
     * @return Application
     */
    function app() {
        return Karthus::$app;
    }
}

if(!function_exists('is_cli')){
    /**
     * 是否为 CLI 模式
     * @return bool
     */
    function is_cli() {
        return PHP_SAPI === 'cli';
    }
}

if(!function_exists('is_win')){
    /**
     * 是否为 Win 系统
     * @return bool
     */
    function is_win() {
        if (is_mac()) {
            return false;
        }
        return stripos(PHP_OS, 'WIN') !== false;
    }
}

if(!function_exists('is_mac')){
    /**
     * 是否为 Mac 系统
     * @return bool
     */
    function is_mac() {
        return stripos(PHP_OS, 'Darwin') !== false;
    }
}

if(!function_exists('env')){
    /**
     * Gets the value of an environment variable.
     *
     * @param string     $key
     * @param null|mixed $default
     * @return array|bool|false|string|void
     */
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return value($default);
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }
        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value) {
        return $value instanceof \Closure ? $value() : $value;
    }
}
