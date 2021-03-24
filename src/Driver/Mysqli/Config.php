<?php
declare(strict_types=1);
namespace Karthus\Driver\Mysqli;

use Karthus\Spl\SplBean;

class Config extends SplBean {
    protected $host;
    protected $user;
    protected $password;
    protected $database;//数据库
    protected $port     = 3306;
    protected $timeout  = 30;
    protected $charset  = 'utf8mb4';
    protected $strict_type  =  false; //开启严格模式，返回的字段将自动转为数字类型
    protected $fetch_mode   = false;//开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行
    protected $maxReconnectTimes = 3;

    /**
     * @return string
     */
    public function getCharset(): string {
        return $this->charset;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset) {
        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function getHost() : string {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host) {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getTimeout() : int {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getDatabase() :string {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getMaxReconnectTimes() :int {
        return $this->maxReconnectTimes;
    }

    /**
     * @return string
     */
    public function getPassword() :string {
        return $this->password;
    }

    /**
     * @return int
     */
    public function getPort() :int {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUser() :string {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function isFetchMode() :bool {
        return $this->fetch_mode;
    }

    /**
     * @return bool
     */
    public function isStrictType() :bool {
        return $this->strict_type;
    }

    /**
     * @param string $database
     */
    public function setDatabase(string $database) {
        $this->database = $database;
    }

    /**
     * @param bool $fetch_mode
     */
    public function setFetchMode(bool $fetch_mode) {
        $this->fetch_mode = $fetch_mode;
    }

    /**
     * @param int $maxReconnectTimes
     */
    public function setMaxReconnectTimes(int $maxReconnectTimes) {
        $this->maxReconnectTimes = $maxReconnectTimes;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password) {
        $this->password = $password;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port) {
        $this->port = $port;
    }

    /**
     * @param bool $strict_type
     */
    public function setStrictType(bool $strict_type) {
        $this->strict_type = $strict_type;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout) {
        $this->timeout = $timeout;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user) {
        $this->user = $user;
    }
}
