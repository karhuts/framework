<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Component\Singleton;
use Karthus\Exception\Exception;
use Swoole\Coroutine;

class Manager {
    use Singleton;

    /**
     * 连接信息
     *
     * @var array
     */
    protected $connections = [];

    /**
     * 事务上下文
     *
     * @var array
     */
    protected $transactionContext = [];

    /**
     * 添加连接池
     *
     * @param ConnectionInterface $connection
     * @param string              $connectionName
     * @return Manager
     */
    public function addConnection(ConnectionInterface $connection,string $connectionName = 'default'): Manager {
        $this->connections[$connectionName] = $connection;
        return $this;
    }

    /**
     * 获取连接
     *
     * @param string $connectionName
     * @return ConnectionInterface|null
     */
    public function getConnection(string $connectionName = 'default'):?ConnectionInterface {
        if(isset($this->connections[$connectionName])){
            return $this->connections[$connectionName];
        }
        return null;
    }

    /**
     * @param QueryBuilder $builder
     * @param bool $raw
     * @param string|ClientInterface $connection
     * @param float|null $timeout
     * @return Result
     * @throws Exception
     * @throws \Throwable
     */
    public function query(string $builder, bool $raw = false, $connection = 'default', float $timeout = null):Result {
        if(is_string($connection)){
            $_connection = $this->getConnection($connection);
            if(!$_connection){
                throw new Exception("connection : {$connection} not register");
            }
            $client = $_connection->defer($timeout);
            if(empty($client)){
                throw new Exception("connection : {$connection} is empty");
            }
        }else{
            $client = $connection;
        }

        $start  = microtime(true);
        $ret    = $client->query($builder);
        if($this->onQuery){
            $temp = clone $builder;
            call_user_func($this->onQuery,$ret,$temp,$start);
        }
        if(in_array('SQL_CALC_FOUND_ROWS',$builder->getLastQueryOptions())){
            $temp = new QueryBuilder();
            $temp->raw('SELECT FOUND_ROWS() as count');
            $count = $client->query($temp,true);
            if($this->onQuery){
                call_user_func($this->onQuery,$count,$temp,$start,$client);
            }
            $ret->setTotalCount($count->getResult()[0]['count']);
        }
        return $ret;
    }

    /**
     * 引用
     *
     * @param callable   $call
     * @param string     $connectionName
     * @param float|null $timeout
     * @return mixed
     * @throws \Throwable
     */
    public function invoke(callable $call,string $connectionName = 'default',float $timeout = null) {
        $connection = $this->getConnection($connectionName);
        if($connection){
            $client = $connection->getClientPool()->getObject($timeout);
            if($client){
                try{
                    return call_user_func($call, $client);
                }catch (\Throwable $exception){
                    throw $exception;
                }finally{
                    $connection->getClientPool()->recycle($client);
                }
            }else{
                throw new Exception("connection : {$connectionName} is empty");
            }
        }else{
            throw new Exception("connection : {$connectionName} not register");
        }
    }

    /**
     * 清理事务上下文
     *
     * @param null $connectName
     * @return bool
     */
    protected function clearTransactionContext($connectName = null) {
        $cid = Coroutine::getCid();
        if (!isset($this->transactionContext[$cid])){
            return false;
        }

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
