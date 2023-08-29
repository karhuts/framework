<?php
declare(strict_types=1);
namespace karthus;

use Closure;
use karthus\route\Http\Constant;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use karthus\support\view\Simple;
use Phar;

/**
 * Config path
 * @param string $path
 * @return string
 */
function config_path(string $path = ''): string
{
    return path_combine(ROOT . DIRECTORY_SEPARATOR . 'config', $path);
}


/**
 * if the param $path equal false,will return this program current execute directory
 * @param bool|string $path
 * @return string
 */
function base_path(bool|string $path = ''): string {
    if (false === $path) {
        return run_path();
    }
    return path_combine(ROOT, $path);
}

/**
 * @param string|bool $path
 * @return string
 */
function cache_path(string|bool $path = ''): string {
    if (false === $path) {
        return runtime_path("cache");
    }
    return path_combine(runtime_path(), $path);
}

/**
 * return the program execute directory
 * @param string $path
 * @return string
 */
function run_path(string $path = ''): string
{
    static $runPath = '';
    if (!$runPath) {
        $runPath = is_phar() ? dirname(Phar::running(false)) : config("app.runtime_path", ROOT);
    }
    return path_combine($runPath, $path);
}


/**
 * Runtime path
 * @param string $path
 * @return string
 */
function runtime_path(string $path = ''): string
{
    static $runtimePath = '';
    if (!$runtimePath) {
        $runtimePath = run_path('runtime');
    }
    return path_combine($runtimePath, $path);
}


/**
 * Generate paths based on given information
 * @param string $front
 * @param string $back
 * @return string
 */
function path_combine(string $front, string $back): string
{
    return $front . ($back ? (DIRECTORY_SEPARATOR . ltrim($back, DIRECTORY_SEPARATOR)) : $back);
}

/**
 * Is phar
 * @return bool
 */
function is_phar(): bool
{
    return class_exists(Phar::class, false) && Phar::running();
}


/**
 * @param int $status
 * @param string $message
 * @param array $data
 * @param array $extra
 * @return array
 */
function http_responses_message(int $status = Constant::API_CODE_OK,
                                string $message = '',
                                array $data = [],
                                array $extra = []) : array {
    if ($message === "") {
        $message = ErrorCode::$MSG[$status] ?? "OK";
    }
    return [
        'code'      => $status,
        'message'   => $message,
        'data'      => $data,
        'extra'     => $extra,
    ];
}

/**
 * @return array
 */
function http_get_body(): array {
    $body = @file_get_contents('php://input');
    if (empty($body)) {
        return [];
    }

    $__ = json_decode($body, true);
    return $__ ?? [];
}


/**
 * Get config
 * @param string|null $key
 * @param $default
 * @return array|mixed|null
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
 * @param string $key
 * @param mixed|null $default
 * @return array|bool|mixed|string|null
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

/**
 * @return ServerRequest
 */
function request(): ServerRequest
{
    return Context::get(ServerRequest::class);
}


/**
 * View response
 * @param string $template
 * @param array $vars
 * @return HtmlResponse
 */
function view(string $template, array $vars = []): HtmlResponse
{
    $handler = config("view.handler");
    $data = match($handler) {
        Simple::class => view_simple($template, $vars),
    };
    return new Response\HtmlResponse($data, 200);
}

/**
 * @return HtmlResponse
 */
function view_404(): HtmlResponse
{
    return view("404")->withStatus(404);
}


function view_505(): HtmlResponse
{
    return view("505")->withStatus(505);
}

/**
 * @param string $template
 * @param array $vars
 * @return string
 */
function view_simple(string $template, array $vars = []): string{
    return support\view\Simple::render($template, $vars);
}

/**
 * @param string $key
 * @param $value
 * @return void
 */
function assign(string $key, $value) : void
{
    $handler = config('view.handler');
    $handler::assign($key, $value);
}

/**
 * @param string $message
 * @param bool $is_error
 * @param string $url
 * @param bool $backHistory
 * @return HtmlResponse
 */
function redirect(string $message = "", bool $is_error = false,
                  string $url = "", bool $backHistory = false): HtmlResponse {
    if ($backHistory === true) {
        $url = "";
    }
    assign('message', $message);
    assign('is_error', $is_error);
    assign('url', $url);
    return view("redirect");
}
