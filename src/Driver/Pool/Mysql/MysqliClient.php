<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Driver\Mysqli\Mysqli;
use Karthus\Driver\Pool\PoolObjectInterface;
use Karthus\Exception\Exception;

class MysqliClient extends Mysqli implements ClientInterface , PoolObjectInterface {

    /**
     * @var string
     */
    private $lastQuery;

    /**
     * @var Result
     */
    private $lastQueryResult;


    /**
     * @param string     $builder
     * @param float|null $timeout
     * @return Result
     * @throws \Throwable
     */
    public function query(string $builder, float $timeout = null): Result{
        $result                = new Result();
        if($timeout === null){
            $timeout    = $this->getConfig()->getTimeout();
        }
        try{
            $ret            = [];
            $stmt           = $this->getMysqlClient()->prepare($builder, $timeout);
            if($stmt){
                $stmt->execute();
                $ret  = $stmt->fetchAll();
            }
            $errno          = $this->getMysqlClient()->errno;
            $error          = $this->getMysqlClient()->error;
            $insert_id      = $this->getMysqlClient()->insert_id;
            $affected_rows  = $this->getMysqlClient()->affected_rows;

            /**
             * 重置mysqli客户端成员属性，避免下次使用
             */
            $this->getMysqlClient()->errtno          = 0;
            $this->getMysqlClient()->error           = '';
            $this->getMysqlClient()->insert_id      = 0;
            $this->getMysqlClient()->affected_rows  = 0;

            $result->setResult($ret);
            $result->setAffectedRows($affected_rows);
            $result->setLastError($error);
            $result->setLastErrorNo($errno);
            $result->setLastInsertId($insert_id);

            $this->lastQueryResult = $result;
            $this->lastQuery       = $builder;
        }catch (\Throwable $throwable){
            throw $throwable;
        } finally {
            // 这里需要进行回收？
            if($errno){
                $this->getMysqlClient()->close();
                throw new Exception($error);
            }
        }

        return $result;
    }
    /**
     * @inheritDoc
     */
    public function gc() {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function objectRestore() {}

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function beforeUse(): bool {
        return !!$this->getMysqlClient()->connected;
    }

    /**
     * 最后的sql构造
     * @return mixed
     */
    public function lastQuery(): string {
        return $this->lastQuery;
    }

    /**
     * 最后的查询结果
     * @return mixed
     */
    public function lastQueryResult():? Result {
        return $this->lastQueryResult;
    }

    /**
     * @param string $str
     * @return string
     * @throws \Throwable
     */
    public function escape(string $str): string {
        return $this->getMysqlClient()->escape($str);
    }

    /**
     * 开启事务。与 commit 和 rollback 结合实现 MySQL 事务处理。
     * @return bool
     * @throws \Throwable
     */
    public function begin(): bool {
        return $this->getMysqlClient()->begin();
    }

    /**
     * 提交事务。
     *
     * @return bool
     * @throws \Throwable
     */
    public function commit(): bool {
        return $this->getMysqlClient()->commit();
    }

    /**
     * 回滚事务。
     *
     * @return bool
     * @throws \Throwable
     */
    public function rollback(): bool {
        return $this->getMysqlClient()->rollback();
    }
}
