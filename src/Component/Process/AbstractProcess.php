<?php
declare(strict_types=1);
namespace Karthus\Process;

use Swoole\Coroutine\Scheduler;
use Swoole\Event;
use Swoole\Process;
use Swoole\Timer;

abstract class AbstractProcess {
    private $swooleProcess;
    /** @var Config */
    private $config;

    /**
     * AbstractProcess constructor.
     *
     * @param array $args
     */
    public function __construct(...$args) {
        $arg1 = array_shift($args);
        if ($arg1 instanceof Config) {
            $this->config = $arg1;
        } else {
            $this->config = new Config();
            $this->config->setProcessName($arg1);
            $arg = array_shift($args);
            $this->config->setArg($arg);
            $redirectStdinStdout = (bool) array_shift($args) ?: false;
            $this->config->setRedirectStdinStdout($redirectStdinStdout);
            $pipeType = array_shift($args);
            $pipeType = $pipeType === null ? Config::PIPE_TYPE_SOCK_DGRAM : $pipeType;
            $this->config->setPipeType($pipeType);
            $enableCoroutine = (bool) array_shift($args) ?: false;
            $this->config->setEnableCoroutine($enableCoroutine);
        }
        $this->swooleProcess = new Process([$this, '__start'], $this->config->isRedirectStdinStdout(), $this->config->getPipeType(), $this->config->isEnableCoroutine());
    }

    public function getProcess(): Process {
        return $this->swooleProcess;
    }

    public function addTick($ms, callable $call): ?int {
        return Timer::getInstance()->loop(
            $ms, $call
        );
    }

    public function clearTick(int $timerId): ?int {
        return Timer::getInstance()->clear($timerId);
    }

    public function delay($ms, callable $call): ?int {
        return Timer::getInstance()->after($ms, $call);
    }

    /*
     * 服务启动后才能获得到pid
     */
    public function getPid(): ?int {
        if (isset($this->swooleProcess->pid)) {
            return $this->swooleProcess->pid;
        } else {
            return null;
        }
    }

    function __start(Process $process) {
        if (!in_array(PHP_OS, ['Darwin', 'CYGWIN', 'WINNT']) && !empty($this->getProcessName())) {
            $process->name($this->getProcessName());
        }
        swoole_event_add($this->swooleProcess->pipe, function () {
            try {
                $this->onPipeReadable($this->swooleProcess);
            } catch (\Throwable $throwable) {
                $this->onException($throwable);
            }
        });
        Process::signal(SIGTERM, function () use ($process) {
            swoole_event_del($process->pipe);
            /*
             * 清除全部定时器
             */
            Timer::clearAll();
            Process::signal(SIGTERM, null);
            Event::exit();
        });
        register_shutdown_function(function () {
            $schedule = new Scheduler();
            $schedule->add(function () {
                try {
                    $this->onShutDown();
                } catch (\Throwable $throwable) {
                    $this->onException($throwable);
                }
                Timer::clearAll();
            });
            $schedule->start();
        });

        try {
            $this->run($this->config->getArg());
        } catch (\Throwable $throwable) {
            $this->onException($throwable);
        }
    }

    public function getArg() {
        return $this->config->getArg();
    }

    public function getProcessName() {
        return $this->config->getProcessName();
    }

    protected function getConfig(): Config {
        return $this->config;
    }

    /**
     * @param \Throwable $throwable
     * @param mixed      ...$args
     * @throws \Throwable
     */
    protected function onException(\Throwable $throwable, ...$args) {
        throw $throwable;
    }

    protected abstract function run($arg);

    protected function onShutDown() {

    }

    protected function onPipeReadable(Process $process) {
        /*
         * 由于Swoole底层使用了epoll的LT模式，因此swoole_event_add添加的事件监听，
         * 在事件发生后回调函数中必须调用read方法读取socket中的数据，否则底层会持续触发事件回调。
         */
        $process->read();
    }
}
