<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

use Karthus\Component\Singleton;
use Karthus\Exception\PoolException;
use Karthus\Helper\Random;

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
     * 注册一个进程池
     *
     * @param string $className 类名
     * @param string $poolName  进程池名
     * @param int    $maxNum    最大数量
     * @return PoolConf
     * @throws \ReflectionException
     */
    public function register(string $className, string $poolName = '', int $maxNum = 20):PoolConf {
        if($poolName === '') {
            // 随机一个进程池名出来
            $poolName = 'C' . Random::character(16);
        }
        $ref      = new \ReflectionClass($className);
        if($ref->isSubclassOf(AbstractPool::class)){
            $conf = clone $this->defaultConfig;
            $conf->setMaxObjectNum($maxNum);
            $this->pool[$poolName] = [
                'class'     => $className,
                'config'    => $conf
            ];
            return $conf;
        }else{
            throw new PoolException("class {$className} not a sub class of AbstractPool class");
        }
    }

    /**
     * 获取进程池
     *
     * 请在进程克隆后，也就是worker start后，每个进程中独立使用
     *
     * @param string $className
     * @param string $poolName
     * @return AbstractPool|null
     */
    public function getPool(string $className, string $poolName):?AbstractPool {
        if(isset($this->pool[$poolName])){
            $item = $this->pool[$poolName];
            if($item instanceof AbstractPool){
                return $item;
            }else{
                $class = $item['class'];
                if(isset($item['config'])){
                    $obj = new $class($item['config']);
                    $this->pool[$poolName] = $obj;
                }else{
                    $config     = clone $this->defaultConfig;
                    $createCall = $item['call'];
                    $obj        = new $class($config, $createCall);
                    $this->pool[$poolName] = $obj;
                }
                return $this->getPool($className, $poolName);
            }
        }else{
            //先尝试动态注册
            //TODO 继续完善
            $ret     = false;
            try{
                $ret = $this->register($className, $poolName);
            }catch (\Throwable $throwable){
            }
            if($ret){
                return $this->getPool($className, $poolName);
            }
            return null;
        }
    }
}
