<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool;

use Karthus\Exception\PoolObjectNumError;
use Karthus\Spl\SplBean;

class PoolConf extends SplBean {
    /**
     * 执行间隔 30S
     *
     * @var float|int
     */
    protected $intervalCheckTime = 30 * 1000;
    /**
     * 最大空闲时间
     *
     * @var int
     */
    protected $maxIdleTime = 15;
    /**
     * 最大进程池数
     *
     * @var int
     */
    protected $maxObjectNum = 20;
    /**
     * 最小
     *
     * @var int
     */
    protected $minObjectNum = 5;
    /**
     * 获取对象超时时间，3S
     *
     * @var float
     */
    protected $getObjectTimeout = 3.0;
    /**
     * @var array
     */
    private $extraConf;


    /**
     * @return float|int
     */
    public function getIntervalCheckTime() {
        return $this->intervalCheckTime;
    }

    /**
     * @param $intervalCheckTime
     * @return PoolConf
     */
    public function setIntervalCheckTime($intervalCheckTime): PoolConf {
        $this->intervalCheckTime = $intervalCheckTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxIdleTime(): int {
        return $this->maxIdleTime;
    }

    /**
     * @param int $maxIdleTime
     * @return PoolConf
     */
    public function setMaxIdleTime(int $maxIdleTime): PoolConf {
        $this->maxIdleTime = $maxIdleTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxObjectNum(): int {
        return $this->maxObjectNum;
    }

    /**
     * @param int $maxObjectNum
     * @return PoolConf
     */
    public function setMaxObjectNum(int $maxObjectNum): PoolConf {
        if($this->minObjectNum >= $maxObjectNum){
            throw new PoolObjectNumError('min num is bigger than max');
        }
        $this->maxObjectNum = $maxObjectNum;
        return $this;
    }

    /**
     * @return float
     */
    public function getGetObjectTimeout(): float {
        return $this->getObjectTimeout;
    }

    /**
     * @param float $getObjectTimeout
     * @return PoolConf
     */
    public function setGetObjectTimeout(float $getObjectTimeout): PoolConf {
        $this->getObjectTimeout = $getObjectTimeout;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExtraConf() {
        return $this->extraConf;
    }

    /**
     * @param $extraConf
     * @return PoolConf
     */
    public function setExtraConf($extraConf): PoolConf {
        $this->extraConf = $extraConf;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinObjectNum(): int {
        return $this->minObjectNum;
    }

    /**
     * @param int $minObjectNum
     * @return PoolConf
     */
    public function setMinObjectNum(int $minObjectNum): PoolConf {
        if($minObjectNum >= $this->maxObjectNum){
            throw new PoolObjectNumError('min num is bigger than max');
        }
        $this->minObjectNum = $minObjectNum;
        return $this;
    }
}
