<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use ArrayAccess;
use JsonSerializable;
use Karthus\Component\Singleton;
use Karthus\Exception\Exception;
use Swoole\Coroutine\MySQL\Statement;

abstract class AbstractModel implements ArrayAccess, JsonSerializable {
    use Singleton;

    /** @var string 连接池名称 */
    protected $connectionName = 'default';
    /** @var null|string 临时连接名 */
    private $tempConnectionName = null;

    private $lastQueryResult;
    private $lastQuery;

    /** @var ClientInterface */
    private $client;

    /**
     * 设置执行client
     * @param ClientInterface|null $client
     * @return $this
     */
    public function setClient(?ClientInterface $client) {
        $this->client = $client;
        return $this;
    }


    /**
     * 连接名设置
     * @param string $name
     * @param bool $isTemp
     * @return AbstractModel
     */
    public function connection(string $name, bool $isTemp = false) {
        if ($isTemp) {
            $this->tempConnectionName   = $name;
        } else {
            $this->connectionName       = $name;
        }
        return $this;
    }


    /**
     * 获取使用的链接池名
     * @return string|null
     */
    public function getConnectionName() {
        if ($this->tempConnectionName) {
            $connectionName = $this->tempConnectionName;
        } else {
            $connectionName = $this->connectionName;
        }
        return $connectionName;
    }


    /**
     * 执行QueryBuilder
     * @param string $builder
     * @param bool $raw
     * @return mixed
     * @throws \Throwable
     */
    public function query(string $builder) {
        $this->lastQuery    = $builder;
        if ($this->tempConnectionName) {
            $connectionName = $this->tempConnectionName;
        } else {
            $connectionName = $this->connectionName;
        }
        try {
            if($this->client){
                $ret = Manager::getInstance()->query($builder, $this->client);
            }else{
                $ret = Manager::getInstance()->query($builder, $connectionName);
            }
            $this->lastQueryResult = $ret;
            return $ret->getResult();
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }

    /**
     * 取出链接
     *
     * @param string     $name
     * @param float|NULL $timeout
     * @return ClientInterface|null
     */
    public static function defer(string $name, float $timeout = null) {
        try {
            $model = new static();
        } catch (Exception $e) {
            return null;
        }
        $connectionName = $model->connectionName;

        return Manager::getInstance()->getConnection($connectionName)->defer($name, $timeout);
    }

    /**
     * invoke执行Model
     * @param ClientInterface $client
     * @param array $data
     * @return AbstractModel|$this
     * @throws Exception
     */
    public static function invoke(ClientInterface $client): AbstractModel {
        return (self::getInstance())->setClient($client);
    }

    /**
     * 最后结果
     *
     * @return Result|null
     */
    public function lastQueryResult(): ?Result {
        return $this->lastQueryResult;
    }

    /**
     * 最后执行SQL
     *
     * @return string|null
     */
    public function lastQuery(): ?string {
        return $this->lastQuery;
    }
}
