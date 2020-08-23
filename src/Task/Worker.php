<?php
declare(strict_types=1);

namespace Karthus\Task;

use Karthus\Component\Process\Socket\AbstractUnixProcess;
use Karthus\Task\AbstractInterface\TaskInterface;
use Swoole\Atomic\Long;
use Swoole\Coroutine\Socket;
use Swoole\Table;
use Swoole\Timer;
use function Opis\Closure\serialize;
use function Opis\Closure\unserialize;

class Worker extends AbstractUnixProcess{
    protected $workerIndex;
    /**
     * @var Table
     */
    protected $infoTable;
    /** @var Long */
    protected $taskIdAtomic;
    /**
     * @var Config
     */
    protected $taskConfig;

    /**
     * @param $arg
     * @return mixed|void
     * @throws \Throwable
     */
    public function run($arg) {
        $this->workerIndex  = $arg['workerIndex'];
        $this->infoTable    = $arg['infoTable'];
        $this->taskIdAtomic = $arg['taskIdAtomic'];
        $this->taskConfig   = $arg['taskConfig'];
        $data               = [
            'running'       => 0,
            'success'       => 0,
            'fail'          => 0,
            'pid'           => intval($this->getProcess()->pid),
            'workerIndex'   => intval($this->workerIndex),
        ];
        $this->infoTable->set((string) $this->workerIndex, $data);
        /*
         * 定时检查任务队列
         */
        if($this->taskConfig->getTaskQueue()){
            Timer::tick(800,function (){
                try{
                    if($this->infoTable->incr($this->workerIndex,'running',1) <= $this->taskConfig->getMaxRunningNum()){
                        $task = $this->taskConfig->getTaskQueue()->pop();
                        if($task){
                            $taskId = $this->taskIdAtomic->add(1);
                            $this->runTask($task,$taskId);
                        }
                    }
                }catch (\Throwable $exception){
                    $this->onException($exception);
                }finally{
                    $this->infoTable->decr((string) $this->workerIndex, 'running', 1);
                }
            });
        }
        parent::run($arg);
    }

    /**
     * @param Socket $socket
     * @throws \Throwable
     */
    public function onAccept(Socket $socket) {
        // 收取包头4字节计算包长度 收不到4字节包头丢弃该包
        $header = $socket->recvAll(4, 1);
        if (strlen($header) !== 4) {
            $socket->sendAll(Protocol::pack(serialize(Task::ERROR_PACKAGE_ERROR)));
            $socket->close();
            return;
        }
        // 收包头声明的包长度 包长一致进入命令处理流程
        // 多处close是为了快速释放连接
        $allLength = Protocol::packDataLength($header);
        $data = $socket->recvAll($allLength, 1);
        if (strlen($data) != $allLength) {
            $socket->sendAll(Protocol::pack(serialize(Task::ERROR_PACKAGE_ERROR)));
            $socket->close();
            return;
        }
        /** @var Package $package */
        $package = unserialize($data);
        if(!$package instanceof Package){
            $socket->sendAll(Protocol::pack(serialize(Task::ERROR_PACKAGE_ERROR)));
            $socket->close();
            return;
        }

        try{
            if($this->infoTable->incr((string) $this->workerIndex,'running',1) <= $this->taskConfig->getMaxRunningNum()){
                $taskId = $this->taskIdAtomic->add(1);
                switch ($package->getType()){
                    case $package::ASYNC:{
                        $socket->sendAll(Protocol::pack(serialize($taskId)));
                        $this->runTask($package,$taskId);
                        $socket->close();
                        break;
                    }
                    case $package::SYNC:{
                        $reply = $this->runTask($package,$taskId);
                        $socket->sendAll(Protocol::pack(serialize($reply)));
                        $socket->close();
                        break;
                    }
                }
            }else{
                //异步任务才进队列，
                if(($package->getType() != $package::SYNC) && $this->taskConfig->getTaskQueue()){
                    $ret = $this->taskConfig->getTaskQueue()->push($package);
                    if($ret){
                        $socket->sendAll(Protocol::pack(serialize(Task::PUSH_IN_QUEUE)));
                    }else{
                        $socket->sendAll(Protocol::pack(serialize(Task::PUSH_QUEUE_FAIL)));
                    }
                }else{
                    $socket->sendAll(Protocol::pack(serialize(Task::ERROR_PROCESS_BUSY)));
                }
                $socket->close();
            }
        }catch (\Throwable $exception){
            if($package->getType() == $package::SYNC){
                $socket->sendAll(Protocol::pack(serialize(Task::ERROR_TASK_ERROR)));
                $socket->close();
            }
            throw $exception;
        }finally{
            $this->infoTable->decr((string) $this->workerIndex, 'running', 1);
        }
    }

    /**
     * @param \Throwable $throwable
     * @param mixed      ...$args
     * @throws \Throwable
     */
    protected function onException(\Throwable $throwable, ...$args) {
        if(is_callable($this->taskConfig->getOnException())){
            call_user_func($this->taskConfig->getOnException(),$throwable,$this->workerIndex);
        }else{
            throw $throwable;
        }
    }

    /**
     * @param Package $package
     * @param int     $taskId
     * @return mixed|void|null
     * @throws \Throwable
     */
    protected function runTask(Package $package,int $taskId) {
        try{
            $task = $package->getTask();
            $reply = null;
            if(is_string($task) && class_exists($task)){
                $ref = new \ReflectionClass($task);
                if($ref->implementsInterface(TaskInterface::class)){
                    /** @var TaskInterface $ins */
                    $task = $ref->newInstance();
                }
            }
            if($task instanceof TaskInterface){
                try{
                    $reply = $task->run($taskId, $this->workerIndex);
                }catch (\Throwable $throwable){
                    $reply = $task->onException($throwable, $taskId, $this->workerIndex);
                }
            }else if(is_callable($task)){
                $reply = call_user_func($task, $taskId, $this->workerIndex);
            }
            if(is_callable($package->getOnFinish())){
                $reply = call_user_func($package->getOnFinish(),$reply, $taskId, $this->workerIndex);
            }
            $this->infoTable->incr((string) $this->workerIndex,'success',1);
            return $reply;
        }catch (\Throwable $throwable){
            $this->infoTable->incr((string) $this->workerIndex,'fail',1);
            $this->onException($throwable);
            return;
        }
    }
}
