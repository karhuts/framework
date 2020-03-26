<?php
declare(strict_types=1);
namespace Karthus\Driver\Redis;

use Karthus\Spl\SplBean;

class Config extends SplBean {
    public const SERIALIZE_NONE = 0;
    public const SERIALIZE_PHP  = 1;
    public const SERIALIZE_JSON = 2;

    protected $host     ='127.0.0.1';
    protected $port     = 6379;
    protected $auth;
    protected $timeout  = 3.0;
    protected $reconnectTimes = 3;
    protected $serialize = self::SERIALIZE_NONE;

    /**
     * @param float $timeout
     */
    public function setTimeout(float $timeout) {
        $this->timeout = $timeout;
    }

    /**
     * @param int $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @return float
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param int $serialize
     */
    public function setSerialize(int $serialize) {
        $this->serialize = $serialize;
    }

    /**
     * @param int $reconnectTimes
     */
    public function setReconnectTimes(int $reconnectTimes) {
        $this->reconnectTimes = $reconnectTimes;
    }

    /**
     * @param mixed $auth
     */
    public function setAuth($auth) {
        $this->auth = $auth;
    }

    /**
     * @return int
     */
    public function getSerialize() {
        return $this->serialize;
    }

    /**
     * @return int
     */
    public function getReconnectTimes() {
        return $this->reconnectTimes;
    }

    /**
     * @return mixed
     */
    public function getAuth() {
        return $this->auth;
    }
}
