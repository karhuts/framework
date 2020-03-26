<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

use Karthus\Context\ContextManager;
use Karthus\Exception\Exception;
use Swoole\Coroutine;

/**
 * Trait InvokerPool
 *
 * @package Karthus\Driver\Pool
 */
trait InvokerPool {

    /**
     * 也是一个注册，允许回调
     *
     * @param string     $poolName
     * @param callable   $callback
     * @param float|null $timeout
     * @return mixed
     * @throws \Throwable
     */
    public static function invoke(string $poolName, callable $callback, float $timeout = null) {
        $pool = PoolManager::getInstance()->getPool(static::class, $poolName);
        if($pool instanceof AbstractPool){
            $obj = $pool->getObject($timeout);
            if($obj){
                try{
                    $ret = call_user_func($callback, $obj);
                    return $ret;
                }catch (\Throwable $throwable){
                    throw $throwable;
                }finally{
                    $pool->recycle($obj);
                }
            }else{
                throw new Exception(static::class." pool is empty");
            }
        }else{
            throw new Exception(static::class." convert to pool error");
        }
    }

    /**
     * @param null $timeout
     * @return mixed
     * @throws \Throwable
     */
    public static function defer(string $poolName, $timeout = null) {
        $key    = md5(static::class);
        $obj    = ContextManager::getInstance()->get($key);
        if($obj){
            return $obj;
        }else{
            $pool = PoolManager::getInstance()->getPool(static::class, $poolName);
            if($pool instanceof AbstractPool){
                $obj = $pool->getObject($timeout);
                if($obj){
                    Coroutine::defer(function ()use($pool, $obj){
                        $pool->recycle($obj);
                    });
                    ContextManager::getInstance()->set($key, $obj);
                    return $obj;
                }else{
                    throw new Exception(static::class." pool is empty");
                }
            }else{
                throw new Exception(static::class." convert to pool error");
            }
        }
    }
}
