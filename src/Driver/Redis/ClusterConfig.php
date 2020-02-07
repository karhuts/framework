<?php
declare(strict_types=1);
namespace Karthus\Driver\Redis;

/**
 * Redis集群配置
 *
 * Class ClusterConfig
 *
 * @package Karthus\Driver\Redis
 */
class ClusterConfig extends Config {
    public const SERIALIZE_NONE     = 0;
    public const SERIALIZE_PHP      = 1;
    public const SERIALIZE_JSON     = 2;
    /**
     * @var array 集群LIST
     */
    protected $serverList = [
        [
            'host' => '127.0.0.1',
            'port' => 6379,
        ]
    ];
    protected $auth;
    protected $timeout = 3.0;
    protected $reconnectTimes = 3;
    protected $serialize = self::SERIALIZE_NONE;

    /**
     * ClusterConfig constructor.
     *
     * @param array      $serverList
     * @param array|null $data
     * @param bool       $autoCreateProperty
     * @throws \ReflectionException
     */
    public function __construct(array $serverList=[], array $data = null, $autoCreateProperty = false) {
        if(!empty($serverList)){
            ($this->serverList = $serverList);
        }
        parent::__construct($data, $autoCreateProperty);
    }

    /**
     * @return array
     */
    public function getServerList(): array {
        return $this->serverList;
    }

    /**
     * @param array $serverList
     */
    public function setServerList(array $serverList): void {
        $this->serverList = $serverList;
    }

    /**
     * @return float
     */
    public function getTimeout(): float {
        return $this->timeout;
    }

    /**
     * @param float $timeout
     */
    public function setTimeout(float $timeout): void {
        $this->timeout = $timeout;
    }

    /**
     * @return int
     */
    public function getReconnectTimes(): int {
        return $this->reconnectTimes;
    }

    /**
     * @param int $reconnectTimes
     */
    public function setReconnectTimes(int $reconnectTimes): void {
        $this->reconnectTimes = $reconnectTimes;
    }

    /**
     * @return int
     */
    public function getSerialize(): int {
        return $this->serialize;
    }

    /**
     * @param int $serialize
     */
    public function setSerialize(int $serialize): void {
        $this->serialize = $serialize;
    }

    /**
     * @return mixed
     */
    public function getAuth() {
        return $this->auth;
    }

    /**
     * @param mixed $auth
     */
    public function setAuth($auth): void {
        $this->auth = $auth;
    }
}
