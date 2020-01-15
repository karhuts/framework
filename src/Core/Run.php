<?php
declare(strict_types=1);
namespace Karthus\Core;

use Karthus;
use Karthus\Console\CommandLine\Flag;
use Karthus\Http\Message\Response;
use Karthus\Http\Message\ServerRequest;
use Karthus\Http\Server\Middleware\MiddlewareDispatcher;
use Karthus\Http\Server\Server;
use Karthus\Logger\Logger;
use Karthus\Router\Router;
use PhpDocReader\AnnotationException;

class Run {
    /**
     * @var Server
     */
    public $server;
    /**
     * @var Logger
     */
    public $log;
    /**
     * @var Router
     */
    public $route;
    /**
     * StartCommand constructor.
     */
    public function __construct() {
        $app          = context();
        $this->log    = $app->get('log');
        $this->route  = $app->get('route');
        $this->server = $app->get('httpServer');
    }

    /**
     * 主函数
     *
     * @throws \Swoole\Exception
     */
    public function main() {
        // 参数重写
        $host = Flag::string(['h', 'host'], '');
        if ($host) {
            $this->server->host = $host;
        }
        $port = Flag::string(['p', 'port'], '');
        if ($port) {
            $this->server->port = $port;
        }
        $reusePort = Flag::bool(['r', 'reuse-port'], false);
        if ($reusePort) {
            $this->server->reusePort = $reusePort;
        }
        // 捕获信号
        Process::signal([SIGINT, SIGTERM, SIGQUIT], function ($signal) {
            $this->log->info('received signal [{signal}]', ['signal' => $signal]);
            $this->log->info('server shutdown');
            $this->server->shutdown();
            Process::signal([SIGINT, SIGTERM, SIGQUIT], null);
        });
        // 启动服务器
        $this->start();
    }

    /**
     * 启动服务器
     *
     * @throws \Swoole\Exception
     */
    public function start() {
        $server = $this->server;
        $server->handle('/', function (ServerRequest $request, Response $response) {
            $this->handle($request, $response);
        });
        $server->set([]);
        $this->welcome();
        $this->log->info('server start');
        $server->start();
    }

    /**
     * 请求处理
     * @param ServerRequest $request
     * @param Response $response
     * @throws AnnotationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function handle(ServerRequest $request, Response $response) {
        // 路由匹配
        try {
            $method     = $request->getMethod();
            $method     = strtoupper($method);
            $path_info  = $request->getServerParams()['path_info'] ?: '/';
            $path_info  = strval($path_info);
            $result     = $this->route->match($method, $path_info);
        } catch (\Throwable $e) {
            // 404 处理
            static::show404($e, $response);
            return;
        }
        // 保存路由参数
        foreach ($result->getParams() as $key => $value) {
            $request->withAttribute($key, $value);
        }
        // 执行
        try {
            // 执行中间件
            $dispatcher     = new MiddlewareDispatcher($result->getMiddleware(), $request, $response);
            $response       = $dispatcher->dispatch();
            // 执行控制器
            if (!$response->getBody()) {
                $response   = call_user_func($result->getCallback($request, $response), $request, $response);
            }
            /** @var Response $response */
            $response->end();
        } catch (\Throwable $e) {
            // 500 处理
            static::show500($e, $response);
            // 抛出错误，记录日志
            throw $e;
        }
    }
    /**
     * 404处理
     * @param \Throwable $e
     * @param Response $response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public static function show404(\Throwable $e, Response $response) {

    }
    /**
     * 500处理
     * @param \Throwable $e
     * @param Response $response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public static function show500(\Throwable $e, Response $response) {

    }
    /**
     * 欢迎信息
     */
    protected function welcome() {
        $phpVersion    = PHP_VERSION;
        $swooleVersion = swoole_version();
        $host          = $this->server->host;
        $port          = $this->server->port;
        echo <<<EOL
 _  __          _   _
 | |/ /__ _ _ __| |_| |__  _   _ ___
 | ' // _` | '__| __| '_ \| | | / __|
 | . \ (_| | |  | |_| | | | |_| \__ \
 |_|\_\__,_|_|   \__|_| |_|\__,_|___/\n
EOL;
        println('Server         Name:      Karthus-httpd');
        println('System         Name:      ' . strtolower(PHP_OS));
        println("PHP            Version:   {$phpVersion}");
        println("Swoole         Version:   {$swooleVersion}");
        println('Framework      Version:   ' . Karthus::$version);
        println("Listen         Addr:      {$host}");
        println("Listen         Port:      {$port}");
    }
}
