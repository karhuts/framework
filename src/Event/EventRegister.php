<?php
declare(strict_types=1);
namespace Karthus\Event;

use Karthus\Component\MultiContainer;

/**
 * 事件注册
 *
 * Class EventRegister
 *
 * @package Karthus\Event
 */
class EventRegister extends MultiContainer{

    public const onStart = 'start';
    public const onShutdown = 'shutdown';
    public const onWorkerStart = 'workerStart';
    public const onWorkerStop = 'workerStop';
    public const onWorkerExit = 'workerExit';
    public const onTimer = 'timer';
    public const onConnect = 'connect';
    public const onReceive = 'receive';
    public const onPacket = 'packet';
    public const onClose = 'close';
    public const onBufferFull = 'bufferFull';
    public const onBufferEmpty = 'bufferEmpty';
    public const onTask = 'task';
    public const onFinish = 'finish';
    public const onPipeMessage = 'pipeMessage';
    public const onWorkerError = 'workerError';
    public const onManagerStart = 'managerStart';
    public const onManagerStop = 'managerStop';
    public const onRequest = 'request';
    public const onHandShake = 'handShake';
    public const onMessage = 'message';
    public const onOpen = 'open';

    /**
     * EventRegister constructor.
     */
    public function __construct() {
        parent::__construct([
            'start','shutdown','workerStart','workerStop','workerExit','timer',
            'connect','receive','packet','close','bufferFull','bufferEmpty','task',
            'finish','pipeMessage','workerError','managerStart','managerStop',
            'request','handShake','message','open'
        ]);
    }
}
