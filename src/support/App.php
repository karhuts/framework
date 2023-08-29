<?php
declare(strict_types=1);
namespace karthus\support;

use karthus\Bootstrap;
use karthus\route\Http\Exception\Exception;
use karthus\route\Http\Exception\MethodNotAllowedException;
use karthus\route\Http\Exception\NotFoundException;
use karthus\route\Router;
use karthus\Config;
use karthus\Context;
use Dotenv\Dotenv;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use RuntimeException;
use function karthus\run_path;
use function karthus\config;
use function karthus\config_path;
use function karthus\view_505;
use function karthus\view_404;

class App {
    /**
     * @return void
     */
    public static function run(): void
    {
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
            if (!class_exists($className)) {
                $log = "Warning: Class $className setting in config/bootstrap.php not found\r\n";
                echo $log;
                Log::error($log);
                continue;
            }
            /** @var Bootstrap $className */
            $className::run();
        }

        try {
            $request = ServerRequestFactory::fromGlobals(
                $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
            );

            Context::set(ServerRequest::class, $request);

            // 加载路由咯
            $paths = [config_path()];
            Router::load($paths);

            // 路由匹配
            $response = Router::dispatch($request);
        } catch (RuntimeException $exception) {
            print_r($exception);
            exit();
            $response = view_505();
        } catch (NotFoundException $exception) {
            $response = view_404();
        }

        (new SapiEmitter)->emit($response);
    }
}
