<?php
declare(strict_types=1);
namespace Karthus\Database;

/**
 * Interface ConnectionInterface
 *
 * @package Karthus\Database
 */
interface ConnectionInterface {
    /**
     * 连接
     * @return bool
     */
    public function connect();
    /**
     * 关闭连接
     * @return bool
     */
    public function close();

    /**
     * 返回结果集
     *
     * @param string $sql
     * @return \mysqli_result
     */
    public function query(string $sql);

    /**
     * 执行SQL语句
     *
     * @param string $sql
     * @return bool
     */
    public function execute(string $sql);
    /**
     * 返回最后的SQL语句
     * @return string
     */
    public function getLastSql();
    /**
     * 获取最后的日志
     * @return array
     */
    public function getLastLog();
    /**
     * 自动事务
     * @param \Closure $closure
     * @throws \Throwable
     */
    public function transaction(\Closure $closure);
    /**
     * 开始事务
     * @return bool
     */
    public function beginTransaction();
    /**
     * 提交事务
     * @return bool
     */
    public function commit();
    /**
     * 回滚事务
     * @return bool
     */
    public function rollback();
    /**
     * 返回一个RawQuery对象，对象的值将不经过参数绑定，直接解释为SQL的一部分，适合传递数据库原生函数
     * @param string $value
     * @return Expression
     */
    public static function raw(string $value);
    /**
     * 释放连接
     * @return bool
     */
    public function release();
    /**
     * 丢弃连接
     * @return bool
     */
    public function discard();
}
