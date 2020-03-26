<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Swoole\Coroutine\MySQL\Statement;

interface ClientInterface {
    public function begin();
    public function commit();
    public function rollback();
    public function query(string $sql): Result;
    public function lastQuery():? string ;
    public function lastQueryResult():? Result;
    public function escape(string $str):string;
}
