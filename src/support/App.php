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

namespace karthus\support;

use Dotenv\Dotenv;
use karthus\Bootstrap;
use karthus\cache\FileCache;
use karthus\Config;
use karthus\Context;
use karthus\route\Cache\Router as cRouter;
use karthus\Exception\Exception;
use karthus\route\Http\Exception\NotFoundException;
use karthus\route\Http\Exception\RouterDomainNotMatchException;
use karthus\route\Http\Exception\RouterPortNotMatchException;
use karthus\route\Http\Exception\RouterSchemeNotMatchException;
use karthus\route\Router;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

use Throwable;
use function karthus\config;
use function karthus\config_path;
use function karthus\run_path;
use function karthus\view_404;
use function karthus\view_505;

class App
{
    public static function init(): void
    {
        // 最最最开始，进行错误捕捉分析的注册
        static::registerException();
        Config::clear();
        if (class_exists(Dotenv::class) && file_exists(run_path('.env'))) {
            if (method_exists(Dotenv::class, 'createUnsafeImmutable')) {
                Dotenv::createUnsafeImmutable(run_path())->load();
            } else {
                Dotenv::createMutable(run_path())->load();
            }
        }
        // 加载出，route之外的所有配置文件
        Config::load(config_path(), ['route']);
        $errorReporting = config('app.error_reporting');
        if (isset($errorReporting)) {
            error_reporting($errorReporting);
        }
        if ($timezone = config('app.default_timezone')) {
            date_default_timezone_set($timezone);
        }

        // 自动加载
        // 一般用于自定义的 functions
        foreach (config('autoload.files', []) as $file) {
            include_once $file;
        }

        // 这里加载bootstrap了
        foreach (config('bootstrap', []) as $className) {
            if (! class_exists($className)) {
                $log = "Warning: Class {$className} setting in config/bootstrap.php not found\r\n";
                echo $log;
                Log::error($log);
                continue;
            }
            /* @var Bootstrap $className */
            $className::run();
        }
    }

    /**
     * 注册异常处理
     * @return void
     */
    public static function registerException(): void
    {
        set_exception_handler(function (Throwable $exception) {
            restore_exception_handler();
            Exception::errorTpl($exception);
        });
    }

    /**
     * 运行应用
     * @return void
     */
    public static function run(): void
    {
        static::init();

        try {
            $request = ServerRequestFactory::fromGlobals(
                $_SERVER,
                $_GET,
                $_POST,
                $_COOKIE,
                $_FILES
            );

            Context::set(ServerRequest::class, $request);

            // 加载路由咯
            $paths = [config_path()];
            // 路由缓存
            $is_router_cache = config('app.router_cache_enable', false);
            if ($is_router_cache === true) {
                $cache_file = config('app.router_cache_file', '');
                $cacheStore = new FileCache($cache_file, $ttl = 86400);

                cRouter::withCache($cacheStore);
                cRouter::withBuilder(function () use ($paths) {
                    Router::load($paths);
                });
                cRouter::dispatch($request);
            } else {
                // 路由匹配
                Router::load($paths);
            }
            $response = Router::dispatch($request);
        } catch (RuntimeException $exception) {
            $response = view_505($exception->getMessage());
        } catch (NotFoundException $exception) {
            $response = view_404();
        } catch (RouterDomainNotMatchException|RouterPortNotMatchException|RouterSchemeNotMatchException $e) {
            $response = view_404($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $response = view_505($e->getMessage());
        }

        (new SapiEmitter())->emit($response);
    }
}
