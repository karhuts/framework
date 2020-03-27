<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use ArrayAccess;
use JsonSerializable;
use Karthus\Component\Singleton;
use Karthus\Exception\Exception;
use Swoole\Coroutine;

abstract class AbstractModel implements ArrayAccess, JsonSerializable {
    use Singleton;

    /**
     * 连接池名称
     * @var string
     */
    protected $connectionName = 'default';

    /**
     * 最后执行结果
     *
     * @var Result
     */
    protected $lastQueryResult;
    /**
     * 最后查询SQL
     *
     * @var string
     */
    protected $lastQuery;

    /**
     * 事务上下文
     *
     * @var array
     */
    protected $transactionContext = [];

    /**
     * 连接名设置
     *
     * @param string $name
     * @param bool   $isRead
     * @return AbstractModel
     */
    public function connection(string $name, bool $isRead = true): AbstractModel{
        if($isRead === true){
            $name   = "{$name}_READ";
        }else {
            $name   = "{$name}_WRITE";
        }
        $this->connectionName       = strtoupper($name);
        return $this;
    }


    /**
     * 获取使用的链接池名
     * @return string
     */
    public function getConnectionName(): string {
        return strval($this->connectionName);
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
     *
     * @param string $sql 执行的SQL语句
     * @return Result
     * @throws \Throwable
     */
    public function query(string $sql) : Result{
        $this->lastQuery    = $sql;
        try {
            $connectionName = $this->connectionName;
            $ret = Manager::getInstance()->query($sql, $connectionName);
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
