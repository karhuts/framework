<?php
declare(strict_types=1);
namespace Karthus\Database\Persistent;

use Karthus\Database\AbstractConnection;

class Connection extends AbstractConnection {
    /**
     * 返回结果集
     *
     * @param string $sql
     * @return \mysqli_result
     * @throws \Throwable
     */
    public function query(string $sql) {
        return $this->call(__FUNCTION__, [$sql]);
    }

    /**
     * 执行SQL语句
     *
     * @param string $sql
     * @return bool
     * @throws \Throwable
     */
    public function execute(string $sql) {
        return $this->call(__FUNCTION__, [$sql]);
    }

    /**
     * 开始事务
     *
     * @return bool
     * @throws \Throwable
     */
    public function beginTransaction() {
        return $this->call(__FUNCTION__);
    }
    /**
     * 执行方法
     * @param $name
     * @param array $arguments
     * @return mixed
     * @throws \Throwable
     */
    protected function call($name, $arguments = []) {
        try {
            // 执行父类方法
            return call_user_func_array("parent::{$name}", $arguments);
        } catch (\Throwable $e) {
            if (static::isDisconnectException($e)) {
                // 断开连接异常处理
                $this->reconnect();
                // 重新执行方法
                return $this->call($name, $arguments);
            } else {
                // 抛出其他异常
                throw $e;
            }
        }
    }
    /**
     * 判断是否为断开连接异常
     * @param \Throwable $e
     * @return bool
     */
    protected static function isDisconnectException(\Throwable $e) {
        $disconnectMessages = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'failed with errno',
        ];
        $errorMessage       = $e->getMessage();
        foreach ($disconnectMessages as $message) {
            if (false !== stripos($errorMessage, $message)) {
                return true;
            }
        }
        return false;
    }
    /**
     * 重新连接
     */
    protected function reconnect() {
        $this->close();
        $this->connect();
    }
}
