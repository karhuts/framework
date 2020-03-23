<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Swoole\Coroutine\MySQL\Statement;

interface ClientInterface {
    public function fetch():?array;
    public function fetchAll():?array;
    public function nextResult():? bool;
    public function prepare(string $sql, array $data):? Statement;
    public function query(string $sql): ?array;
    public function lastQuery():? string ;
    public function lastQueryResult():? array;
}
