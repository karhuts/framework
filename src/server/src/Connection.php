<?php
declare(strict_types=1);

namespace Karthus\Server;

use Karthus\Server\Exception\ReceiveException;
use Swoole\Exception;

class Connection {
    /**
     * @var \Swoole\Coroutine\Server\Connection
     */
    protected $swooleConnection;

    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var \Swoole\Coroutine\Socket
     */
    public $swooleSocket;

    /**
     * Connection constructor.
     * @param \Swoole\Coroutine\Server\Connection $connection
     * @param ConnectionManager $connectionManager
     */
    public function __construct(\Swoole\Coroutine\Server\Connection $connection, ConnectionManager $connectionManager) {
        $this->swooleConnection  = $connection;
        $this->connectionManager = $connectionManager;
        $this->swooleSocket      = method_exists($connection, 'exportSocket') ? $connection->exportSocket() : $connection->socket; // swoole >= 4.4.13 socket 修改成了 protected
    }

    /**
     * Recv
     * @return string
     * @throws Exception
     */
    public function recv() {
        $data = $this->swooleConnection->recv();
        if ($data === false) { // 接收失败
            $this->close();
            $socket = $this->swooleSocket;
            throw new ReceiveException($socket->errMsg, $socket->errCode);
        }
        if ($data === "") { // 连接关闭
            $this->close();
            $errCode = stripos(PHP_OS, 'Darwin') !== false ? 54 : 104; // mac=54, linux=104
            $errMsg  = swoole_strerror($errCode, 9);
            throw new ReceiveException($errMsg, $errCode);
        }
        return $data;
    }

    /**
     * Send
     * @param string $data
     * @throws Exception
     */
    public function send(string $data) {
        $len  = strlen($data);
        $size = $this->swooleConnection->send($data);
        if ($size === false) {
            $socket = $this->swooleSocket;
            throw new Exception($socket->errMsg, $socket->errCode);
        }
        if ($len !== $size) {
            throw new Exception('The sending data is incomplete, it may be that the socket has been closed by the peer.');
        }
    }

    /**
     * Close
     * @throws Exception
     */
    public function close() {
        if (!$this->swooleConnection->close()) {
            $socket  = $this->swooleSocket;
            $errMsg  = $socket->errMsg;
            $errCode = $socket->errCode;
            if ($errMsg == '' && $errCode == 0) {
                return;
            }
            if ($errMsg == 'Connection reset by peer' && in_array($errCode, [54, 104])) { // mac=54, linux=104
                return;
            }
            throw new Exception($errMsg, $errCode);
        }
        $this->connectionManager->remove($this);
    }
}
