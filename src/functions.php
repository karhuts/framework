<?php
declare(strict_types=1);

use Karthus\Config;

if (!function_exists('getInstance')) {
    /**
     * @param $class
     * @return mixed
     */
    function getInstance($class) {
        return ($class)::getInstance();
    }
}


if (!function_exists('config')) {
    /**
     * @param $name
     * @param $default
     * @return mixed
     */
    function config($name, $default = null) {
        return getInstance(Config::class)->get($name, $default);
    }
}

if (!function_exists('container')) {
    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    function container($key = null, $value = null) {
        if ($key === null) {
            return Container::instance();
        }
        if ($value === null) {
            return Container::instance()->singleton($key);
        }
        return Container::instance()->set($key, $value);
    }
}

if (!function_exists('collection')) {
    /**
     * @param array $data
     * @return Collection
     */
    function collection(array $data = []): Collection {
        return new Collection($data);
    }
}


if (!function_exists('env')) {
    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    function env($key, $default = null){
        return container()->get(Env::class)->get($key, $default);
    }
}