<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

use Karthus\Component\Singleton;
use Karthus\Exception\PoolException;

class PoolManager {
    use Singleton;

    /**
     * @var array
     */
    private $pool = [];
    /**
     * @var PoolConf
     */
    private $defaultConfig;
    /**
     * @var array
     */
    private $anonymousMap = [];

    /**
     * PoolManager constructor.
     */
    public function __construct() {
        $this->defaultConfig = new PoolConf();
    }

    /**
     * @return PoolConf
     */
    public function getDefaultConfig():PoolConf {
        return $this->defaultConfig;
    }

    /**
     * @param string $className
     * @param int    $maxNum
     * @return PoolConf
     * @throws \ReflectionException
     */
    public function register(string $className, $maxNum = 20):PoolConf {
        $ref = new \ReflectionClass($className);
        if($ref->isSubclassOf(AbstractPool::class)){
            $conf = clone $this->defaultConfig;
            $conf->setMaxObjectNum($maxNum);
            $this->pool[$className] = [
                'class'     => $className,
                'config'    => $conf
            ];
            return $conf;
        }else{
            throw new PoolException("class {$className} not a sub class of AbstractPool class");
        }
    }

    /**
     * 请在进程克隆后，也就是worker start后，每个进程中独立使用
     *
     * @param string $key
     * @return AbstractPool|null
     */
    public function getPool(string $key):?AbstractPool {
        if(isset($this->anonymousMap[$key])){
            $key = $this->anonymousMap[$key];
        }
        if(isset($this->pool[$key])){
            $item = $this->pool[$key];
            if($item instanceof AbstractPool){
                return $item;
            }else{
                $class = $item['class'];
                if(isset($item['config'])){
                    $obj = new $class($item['config']);
                    $this->pool[$key] = $obj;
                }else{
                    $config     = clone $this->defaultConfig;
                    $createCall = $item['call'];
                    $obj        = new $class($config,$createCall);
                    $this->pool[$key] = $obj;
                    $this->anonymousMap[get_class($obj)] = $key;
                }
                return $this->getPool($key);
            }
        }else{
            //先尝试动态注册
            $ret = false;
            try{
                $ret = $this->register($key);
            }catch (\Throwable $throwable){
            }
            if($ret){
                return $this->getPool($key);
            }
            return null;
        }
    }
}
