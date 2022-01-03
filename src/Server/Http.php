<?php
declare(strict_types=1);

namespace Karthus\Server;

use Exception;
use Karthus\Application;
use Karthus\Context;
use Karthus\Listener;
use Karthus\Http\Route;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Server as HttpServer;

class Http {
    protected $_server;

    protected $_config;

    /** @var Route */
    protected $_route;

    public function __construct() {
        $config = config('servers');
        $httpConfig = $config['http'];
        $this->_config = $httpConfig;

        $this->_server = new Server(
            $httpConfig['ip'],
            $httpConfig['port'],
            $config['mode'],
            $httpConfig['sock_type']
        );
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('request', [$this, 'onRequest']);

        $this->_server->set($httpConfig['settings']);

        if ($config['mode'] === SWOOLE_BASE) {
            $this->_server->on('managerStart', [$this, 'onManagerStart']);
        } else {
            $this->_server->on('start', [$this, 'onStart']);
        }

        foreach ($httpConfig['callbacks'] as $eventKey => $callbackItem) {
            [$class, $func] = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }

        if (isset($this->_config['process']) && ! empty($this->_config['process'])) {
            foreach ($this->_config['process'] as $processItem) {
                [$class, $func] = $processItem;
                $this->_server->addProcess($class::$func($this->_server));
            }
        }

        $this->_server->start();
    }

    /**
     * @param HttpServer $server
     * @return void
     * @throws Exception
     */
    public function onStart(HttpServer $server): void {
        Application::echoSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('start', $server);
    }

    /**
     * @param HttpServer $server
     * @return void
     * @throws Exception
     */
    public function onManagerStart(HttpServer $server): void {
        Application::echoSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('managerStart', $server);
    }

    /**
     * @param HttpServer $server
     * @param int $workerId
     * @return void
     * @throws Exception
     */
    public function onWorkerStart(HttpServer $server, int $workerId): void {
        $this->_route = Route::getInstance();
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws Exception
     */
    public function onRequest(Request $request, Response $response): void {
        Context::set('SwRequest', $request);
        Context::set('SwResponse', $response);
        Context::set("SwServer", $this->_server);
        $this->_route->dispatch($request, $response);
    }

    /**
     * @param $server
     * @param $fd
     * @param $from_id
     * @param $data
     * @return void
     * @throws Exception
     */
    public function onReceive($server, $fd, $from_id, $data): void {
        $this->_route->dispatch($server, $fd, $data);
    }
}