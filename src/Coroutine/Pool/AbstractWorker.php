<?php
declare(strict_types=1);

namespace Karthus\Coroutine\Pool;

use Karthus\Injector\BeanInjector;
use PhpDocReader\AnnotationException;
use Swoole\Coroutine\Channel;


abstract class AbstractWorker {
    /**
     * 工作池
     * @var Channel
     */
    public $workerPool;
    /**
     * 任务通道
     * @var Channel
     */
    public $jobChannel;
    /**
     * 退出
     * @var Channel
     */
    protected $_quit;
    /**
     * AbstractWorker constructor.
     * @param array $config
     */
    public function __construct(array $config) {
        try {
            BeanInjector::inject($this, $config);
        } catch (AnnotationException $e) {
        } catch (\ReflectionException $e) {
        }
        $this->init();
    }
    /**
     * 初始化
     */
    public function init() {
        $this->jobChannel = new Channel();
        $this->_quit      = new Channel();
    }
    /**
     * 启动
     */
    public function start() {
        go(function () {
            while (true) {
                $this->workerPool->push($this->jobChannel);
                $data = $this->jobChannel->pop();
                if ($data === false) {
                    return;
                }
                $this->handle($data);
            }
        });
        go(function () {
            $this->_quit->pop();
            $this->jobChannel->close();
        });
    }
    /**
     * 停止
     */
    public function stop() {
        go(function () {
            $this->_quit->push(true);
        });
    }
}
