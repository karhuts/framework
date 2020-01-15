<?php
declare(strict_types=1);
namespace Karthus\Database;

use Karthus\Database\Event\ExecutedEvent;
use Karthus\Injector\BeanInjector;
use PhpDocReader\AnnotationException;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractConnection {
    /**
     * 连接资源IP
     *
     * @var string
     */
    public $host = '';
    /**
     * 端口号
     *
     * @var int
     */
    public $port = 3306;

    /**
     * 数据库
     *
     * @var string
     */
    public $database = '';
    /**
     * 数据库用户名
     *
     * @var string
     */
    public $username = 'root';

    /**
     * 数据库密码
     *
     * @var string
     */
    public $password = '';
    /**
     * 事件调度器
     *
     * @var EventDispatcherInterface
     */
    public $eventDispatcher;

    /**
     * mysqli
     *
     * @var \mysqli
     */
    protected $_mysqli;
    /**
     * sql
     *
     * @var string
     */
    protected $_sql = '';
    /**
     * params
     *
     * @var array
     */
    protected $_params = [];
    /**
     * values
     *
     * @var array
     */
    protected $_values = [];
    /**
     * 查询数据
     *
     * @var array
     */
    protected $_queryData = [];

    /**
     * AbstractConnection constructor.
     *
     * @param array $config
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function __construct(array $config = []) {
        BeanInjector::inject($this, $config);
    }

    /**
     * 创建连接
     *
     * @return \mysqli
     */
    protected function createConnection() {
        return new \mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );
    }

    /**
     * 连接
     *
     * @return bool
     */
    public function connect() {
        $this->_mysqli = $this->createConnection();
        return true;
    }

    /**
     * 关闭连接
     *
     * @return bool
     */
    public function close() {
        $this->_mysqli = null;
        return true;
    }

    /**
     * 返回一个RawQuery对象，对象的值将不经过参数绑定，直接解释为SQL的一部分，适合传递数据库原生函数
     *
     * @param string $value
     * @return Expression
     */
    public static function raw(string $value) {
        return new Expression($value);
    }

    /**
     * 清扫构建查询数据
     */
    protected function clear() {
        $this->_sql = '';
        $this->_params = [];
        $this->_values = [];
    }

    /**
     * 调度事件
     */
    protected function dispatchEvent() {
        if (!$this->eventDispatcher) {
            return;
        }
        $log                = $this->getLastLog();
        $event              = new ExecutedEvent();
        $event->sql         = $log['sql'];
        $event->time        = $log['time'];
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * 获取微秒时间
     *
     * @return float
     */
    protected static function microtime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * 执行SQL语句
     *
     * @param string $sql
     * @return bool|\mysqli_result
     */
    public function execute(string $sql) {
        // 执行
        $this->_queryData[0]    = $sql;
        $microtime  = static::microtime();
        $success    = $this->_mysqli->query($sql);
        $time       = round((static::microtime() - $microtime) * 1000, 2);
        $this->_queryData[1] = $time;
        // 清扫
        $this->clear();
        // 调度执行事件
        $this->dispatchEvent();
        // 返回
        return $success;
    }

    /**
     * 返回结果集
     *
     * @return \mysqli_result
     */
    public function query(string $sql) {
        return $this->execute($sql);
    }


    /**
     * 返回最后的SQL语句
     *
     * @return string
     */
    public function getLastSql() {
        list($sql, ) = $this->_queryData;
        return $sql;
    }

    /**
     * 获取最后的日志
     *
     * @return array
     */
    public function getLastLog() {
        list($sql, $time) = $this->_queryData;
        return [
            'sql'      => $sql,
            'time'     => $time,
        ];
    }

    /**
     * 自动事务
     *
     * @param \Closure $closure
     * @throws \Throwable
     */
    public function transaction(\Closure $closure) {
        $this->beginTransaction();
        try {
            $closure();
            // 提交事务
            $this->commit();
        } catch (\Throwable $e) {
            // 回滚事务
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * 开始事务
     *
     * @return bool
     */
    public function beginTransaction() {
        return $this->_mysqli->begin_transaction();
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit() {
        return $this->_mysqli->commit();
    }

    /**
     * 回滚事务
     *
     * @return bool
     */
    public function rollback() {
        return $this->_mysqli->rollBack();
    }
}
