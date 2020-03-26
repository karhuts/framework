<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use ArrayAccess;
use JsonSerializable;
use Karthus\Component\Singleton;
use Karthus\Exception\Exception;
use Swoole\Coroutine;
use Swoole\Coroutine\MySQL\Statement;

abstract class AbstractModel implements ArrayAccess, JsonSerializable {
    use Singleton;

    /** @var string 连接池名称 */
    protected $connectionName = 'default';
    private $lastQueryResult;
    private $lastQuery;

    /** @var ClientInterface */
    private $client;

    /**
     * 事务上下文
     *
     * @var array
     */
    protected $transactionContext = [];

    /**
     * 连接名设置
     * @param string $name
     * @return AbstractModel
     */
    public function connection(string $name) {
        $this->connectionName       = $name;
        return $this;
    }


    /**
     * 获取使用的链接池名
     * @return string|null
     */
    public function getConnectionName() {
        return $this->connectionName;
    }


    /**
     * @return bool
     * @throws \Throwable
     */
    public function transaction(): bool{
        $connection = $this->getConnectionName();
        $cid        = Coroutine::getCid();
        $ret        = Manager::getInstance()->transaction($connection);
        if($ret === true){
            $this->transactionContext[$cid][] = $connection;
        }else {
            $this->rollback();
            return false;
        }
        // defer一个
        Coroutine::defer(function (){
            $cid = Coroutine::getCid();
            if(isset($this->transactionContext[$cid])){
                $this->rollback();
            }
        });
        return true;
    }

    /**
     * 提交事务
     *
     * @param string|null $connectName
     * @return bool
     * @throws \Throwable
     */
    public function commit() : bool{
        $cid = Coroutine::getCid();
        if(isset($this->transactionContext[$cid])){
            // 如果有指定
            $ret    = Manager::getInstance()->commit($this->getConnectionName());
            if($ret !== true){
                $this->rollback();
                return false;
            }
            $this->clearTransactionContext();
            return true;
        }
        return false;
    }

    /**
     * 事务回滚
     *
     * @return bool
     * @throws \Throwable
     */
    public function rollback() : bool{
        $cid = Coroutine::getCid();

        if(isset($this->transactionContext[$cid])){
            // 如果有指定
            $ret    = Manager::getInstance()->rollback($this->getConnectionName());
            if($ret !== true){
                return false;
            }
            $this->clearTransactionContext();
            return true;
        }
        return false;
    }

    /**
     * 执行QueryBuilder
     * @param string $builder
     * @param bool $raw
     * @return mixed
     * @throws \Throwable
     */
    public function query(string $builder) : Result{
        $this->lastQuery    = $builder;
        try {
            $connectionName = $this->connectionName;
            $ret = Manager::getInstance()->query($builder, $connectionName);
            $this->lastQueryResult = $ret;
            return $ret;
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }

    /**
     * @param string $str
     * @return string
     * @throws \Throwable
     */
    public function escape(string $str): string {
        return  Manager::getInstance()->escape($str, $this->connectionName);
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

    /**
     * 清理事务上下文
     *
     * @param null $connectName
     * @return bool
     */
    protected function clearTransactionContext() {
        $cid = Coroutine::getCid();
        if (!isset($this->transactionContext[$cid])){
            return false;
        }

        $connectName    = $this->getConnectionName();
        if ($connectName !== null){
            foreach ($this->transactionContext[$cid] as $key => $name){
                if ($name === $connectName){
                    unset($this->transactionContext[$cid][$key]);
                    return true;
                }
                return false;
            }
        }
        unset($this->transactionContext[$cid]);
        return true;
    }
}
