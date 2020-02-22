<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

use Karthus\Exception\PoolObjectNumError;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

abstract class AbstractPool {
    use InvokerPool;

    private $createdNum = 0;
    /**
     * @var Channel
     */
    private $poolChannel;
    private $objHash    = [];
    /**
     * @var PoolConf
     */
    private $conf;

    /**
     * AbstractPool constructor.
     *
     * @param PoolConf $conf
     */
    public function __construct(PoolConf $conf) {
        if ($conf->getMinObjectNum() >= $conf->getMaxObjectNum()){
            $class = static::class;
            throw new PoolObjectNumError("pool max num is small than min num for {$class} error");
        }
        $this->conf         = $conf;
        $this->poolChannel  = new Channel($conf->getMaxObjectNum());
        if ($conf->getIntervalCheckTime() > 0) {
            Timer::tick($conf->getIntervalCheckTime(), [$this, 'intervalCheck']);
        }
    }

    /*
     * 如果成功创建了,请返回对应的obj
     */
    abstract protected function createObject();

    /**
     * 回收一个对象
     *
     * @param $obj
     * @return bool
     * @throws \Throwable
     */
    public function recycle($obj): bool {
        /*
         * 仅仅允许归属于本pool且不在pool内的对象进行回收
         */
        if($this->isPoolObject($obj) && (!$this->isInPool($obj))){
            $hash                   = $this->getObjectHash($obj);
            //标记为在pool内
            $this->objHash[$hash]   = true;
            if($obj instanceof PoolObjectInterface){
                try{
                    $obj->objectRestore();
                }catch (\Throwable $throwable){
                    //重新标记为非在pool状态,允许进行unset
                    $this->objHash[$hash] = false;
                    $this->unsetObj($obj);
                    throw $throwable;
                }
            }
            $this->poolChannel->push($obj);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取
     *
     * @param float|null $timeout
     * @param int        $tryTimes 为出现异常尝试次数
     * @return mixed|null
     * @throws \Throwable
     */
    public function getObject(float $timeout = null, int $tryTimes = 3) {
        if($timeout === null){
            $timeout = $this->getConfig()->getGetObjectTimeout();
        }
        $object      = null;
        // 如果进程池为空，我就初始化了
        if($this->poolChannel->isEmpty()){
            try{
                $this->initObject();
            }catch (\Throwable $throwable){
                if($tryTimes <= 0){
                    throw $throwable;
                }else{
                    $tryTimes --;
                    return $this->getObject($timeout, $tryTimes);
                }
            }
        }
        $object     = $this->poolChannel->pop($timeout);
        if(is_object($object)){
            if($object instanceof PoolObjectInterface){
                try{
                    if($object->beforeUse() === false){
                        $this->unsetObj($object);
                        if($tryTimes <= 0){
                            return null;
                        }else{
                            $tryTimes--;
                            return $this->getObject($timeout, $tryTimes);
                        }
                    }
                }catch (\Throwable $throwable){
                    $this->unsetObj($object);
                    if($tryTimes <= 0){
                        throw $throwable;
                    }else{
                        $tryTimes--;
                        return $this->getObject($timeout,$tryTimes);
                    }
                }
            }
            $hash                   = $this->getObjectHash($object);
            //标记该对象已经被使用，不在pool中
            $this->objHash[$hash]   = false;
            $object->lastUseTime    = microtime(true);
            return $object;
        }else{
            return null;
        }
    }

    /**
     * 彻底释放一个对象
     *
     * @param object $obj
     * @return bool
     * @throws \Throwable
     */
    public function unsetObj($obj): bool {
        if($this->isPoolObject($obj) && (!$this->isInPool($obj))){
            $hash = $this->getObjectHash($obj);
            unset($this->objHash[$hash]);
            if($obj instanceof PoolObjectInterface){
                try{
                    $obj->gc();
                }catch (\Throwable $throwable){
                    throw $throwable;
                }finally{
                    $this->createdNum --;
                }
            }else{
                $this->createdNum --;
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 超过$idleTime未出队使用的，将会被回收。
     *
     * @param int $idleTime
     * @throws \Throwable
     */
    public function gcObject(int $idleTime) {
        $list = [];
        while (!$this->poolChannel->isEmpty()){
            $item = $this->poolChannel->pop(0.01);
            if(time() - $item->lastUseTime > $idleTime){
                //标记为不在队列内，允许进行gc回收
                $hash = $this->getObjectHash($item);
                $this->objHash[$hash] = false;
                $this->unsetObj($item);
            }else{
                $list[] = $item;
            }
        }
        foreach ($list as $item){
            $this->poolChannel->push($item);
        }
    }

    /**
     * 允许外部调用
     *
     * @throws \Throwable
     */
    public function intervalCheck() {
        $this->gcObject($this->getConfig()->getMaxIdleTime());
        $this->keepMin($this->getConfig()->getMinObjectNum());
    }

    /**
     * @param int|null $num
     * @return int
     * @throws \Throwable
     */
    public function keepMin(?int $num = null): int {
        if($this->createdNum < $num){
            $left = $num - $this->createdNum;
            while ($left > 0 ){
                $this->initObject();
                $left--;
            }
        }
        return $this->createdNum;
    }


    /**
     * 获取配置
     *
     * @return PoolConf
     */
    public function getConfig():PoolConf {
        return $this->conf;
    }

    /**
     * 获取状态
     *
     * @return array
     */
    public function status() : array{
        return [
            'created'   => $this->createdNum,
            'inuse'     => $this->createdNum - $this->poolChannel->stats()['queue_num'],
            'max'       => $this->getConfig()->getMaxObjectNum(),
            'min'       => $this->getConfig()->getMinObjectNum()
        ];
    }

    /**
     * 初始化
     *
     * @return bool
     * @throws \Throwable
     */
    private function initObject():bool {
        $obj    = null;
        $this->createdNum++;
        if($this->createdNum > $this->getConfig()->getMaxObjectNum()){
            $this->createdNum--;
            return false;
        }
        try{
            $obj = $this->createObject();
            if(is_object($obj)){
                $hash                   = $this->getObjectHash($obj);
                $this->objHash[$hash]   = true;
                $obj->lastUseTime       = microtime(true);
                $this->poolChannel->push($obj);
                return true;
            }else{
                $this->createdNum--;
            }
        }catch (\Throwable $throwable){
            $this->createdNum--;
            throw $throwable;
        }
        return false;
    }

    /**
     * 是否是进程池对象
     *
     * @param $obj
     * @return bool
     */
    public function isPoolObject($obj):bool {
        if(!is_object($obj)){
            return false;
        }
        $hash   = $this->getObjectHash($obj);
        return !!isset($this->objHash[$hash]);
    }

    /**
     * @param $obj
     * @return bool
     */
    public function isInPool($obj):bool {
        if(!is_object($obj)){
            return false;
        }
        $hash   = $this->getObjectHash($obj);
        if($this->isPoolObject($obj)){
            return $this->objHash[$hash];
        }else{
            return false;
        }
    }

    /**
     * @param $object
     * @return string
     */
    private function getObjectHash($object): string {
        return spl_object_hash($object);
    }
}
