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

namespace karthus;

use Closure;
use karthus\route\Http\Constant;
use karthus\support\view\Simple;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Phar;

/**
 * Public path.
 */
function public_path(string $path = ''): string
{
    static $publicPath = '';
    if (! $publicPath) {
        $publicPath = config('app.public_path') ?: run_path('public');
    }
    return path_combine($publicPath, $path);
}

/**
 * Config path.
 */
function config_path(string $path = ''): string
{
    return path_combine(BASE_PATH . DIRECTORY_SEPARATOR . 'config', $path);
}

/**
 * App path.
 */
function app_path(string $path = ''): string
{
    return path_combine(BASE_PATH . DIRECTORY_SEPARATOR . 'app', $path);
}

/**
 * if the param $path equal false,will return this program current execute directory.
 */
function base_path(bool|string $path = ''): string
{
    if ($path === false) {
        return run_path();
    }
    return path_combine(BASE_PATH, $path);
}

function cache_path(string|bool $path = ''): string
{
    if ($path === false) {
        return runtime_path('cache');
    }
    return path_combine(runtime_path(), $path);
}

/**
 * return the program execute directory.
 */
function run_path(string $path = ''): string
{
    static $runPath = '';
    if (! $runPath) {
        $runPath = is_phar() ? dirname(Phar::running(false)) : config('app.runtime_path', BASE_PATH);
    }
    return path_combine($runPath, $path);
}

/**
 * Runtime path.
 */
function runtime_path(string $path = ''): string
{
    static $runtimePath = '';
    if (! $runtimePath) {
        $runtimePath = run_path('runtime');
    }
    return path_combine($runtimePath, $path);
}

/**
 * Generate paths based on given information.
 */
function path_combine(string $front, string $back): string
{
    return $front . ($back ? (DIRECTORY_SEPARATOR . ltrim($back, DIRECTORY_SEPARATOR)) : $back);
}

/**
 * Is phar.
 */
function is_phar(): bool
{
    return class_exists(Phar::class, false) && Phar::running();
}

function http_responses_message(
    int $status = Constant::API_CODE_OK,
    string $message = '',
    array $data = [],
    array $extra = []
): array {
    if ($message === '') {
        $message = Constant::statusMessage($status);
    }
    return [
        'code' => $status,
        'message' => $message,
        'data' => $data,
        'extra' => $extra,
    ];
}

function http_get_body(): array
{
    $body = @file_get_contents('php://input');
    if (empty($body)) {
        return [];
    }

    $__ = json_decode($body, true);
    return $__ ?? [];
}

/**
 * Get config.
 * @param null|mixed $default
 * @return null|array|mixed
 */
function config(string $key = null, $default = null): mixed
{
    return Config::get($key, $default);
}

/**
 * Return the default value of the given value.
 */
function value(mixed $value, ...$args)
{
    return $value instanceof Closure ? $value(...$args) : $value;
}

/**
 * Gets the value of an environment variable.
 *
 * @return null|array|bool|mixed|string
 */
function env(string $key, mixed $default = null): mixed
{
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
            return null;
    }
    if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
        return substr($value, 1, -1);
    }
    return $value;
}

function request(): ServerRequest
{
    return Context::get(ServerRequest::class);
}

/**
 * View response.
 */
function view(string $template, array $vars = []): HtmlResponse
{
    $handler = config('view.handler');
    $data = match ($handler) {
        Simple::class => view_simple($template, $vars),
    };
    return new Response\HtmlResponse($data, 200);
}

function view_404(string $message = ''): HtmlResponse
{
    $message = $message ?? '很抱歉，找不到您要查找的页面';
    assign('message', $message);
    return view('404')->withStatus(404);
}

function view_505(string $message = ''): HtmlResponse
{
    assign('message', $message);
    return view('505')->withStatus(505);
}

function view_simple(string $template, array $vars = []): string
{
    return support\view\Simple::render($template, $vars);
}

function assign(string $key, $value): void
{
    $handler = config('view.handler');
    $handler::assign($key, $value);
}

function redirect(
    string $message = '',
    bool $is_error = false,
    string $url = '',
    bool $backHistory = false
): HtmlResponse {
    if ($backHistory === true) {
        $url = '';
    }
    assign('message', $message);
    assign('is_error', $is_error);
    assign('url', $url);
    return view('redirect');
}

/**
 * 通过 name 获取 classname.
 */
function nameToClass(string $name): string
{
    $class = preg_replace_callback(['/-([a-zA-Z])/', '/_([a-zA-Z])/'], function ($matches) {
        return strtoupper($matches[1]);
    }, $name);

    if (! ($pos = strrpos($class, '/'))) {
        $class = ucfirst($class);
    } else {
        $path = substr($class, 0, $pos);
        $class = ucfirst(substr($class, $pos + 1));
        $class = "{$path}/{$class}";
    }
    return $class;
}

function nameToNamespace(string $name): string
{
    $namespace = ucfirst($name);
    $namespace = preg_replace_callback(['/-([a-zA-Z])/', '/(\/[a-zA-Z])/'], function ($matches) {
        return strtoupper($matches[1]);
    }, $namespace);
    return str_replace('/', '\\', ucfirst($namespace));
}

function classToName($class): string
{
    $class = lcfirst($class);
    return preg_replace_callback(['/([A-Z])/'], function ($matches) {
        return '_' . strtolower($matches[1]);
    }, $class);
}

function guessPath(string $base_path, string|bool $name, false $return_full_path = false): false|string
{
    if (! is_dir($base_path)) {
        return false;
    }
    if (! is_string($name)) {
        return false;
    }
    $names = explode('/', trim(strtolower($name), '/'));
    $realname = [];
    $path = $base_path;
    foreach ($names as $name) {
        $finded = false;
        foreach (scandir($path) ?: [] as $tmp_name) {
            if (strtolower($tmp_name) === $name && is_dir("{$path}/{$tmp_name}")) {
                $path = "{$path}/{$tmp_name}";
                $realname[] = $tmp_name;
                $finded = true;
                break;
            }
        }
        if (! $finded) {
            return false;
        }
    }
    $realname = implode(DIRECTORY_SEPARATOR, $realname);
    return $return_full_path ? get_realpath($base_path . DIRECTORY_SEPARATOR . $realname) : $realname;
}
