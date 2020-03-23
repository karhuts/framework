<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

use Karthus\Driver\Mysqli\Mysqli;
use Karthus\Driver\Pool\PoolObjectInterface;
use Swoole\Coroutine\MySQL\Statement;

class MysqliClient extends Mysqli implements ClientInterface , PoolObjectInterface {

    /**
     * @param string $builder
     * @param bool   $rawQuery
     * @return mixed
     * @throws \Throwable
     */
    public function query(string $builder, bool $rawQuery = false):?array{
        return $this->getMysqlClient()->query($builder);
    }

    public function lastQuery(): ?string {
    }

    public function lastQueryResult(): ?array {
        // TODO: Implement lastQueryResult() method.
    }

    /**
     * @inheritDoc
     */
    public function gc() {

    }

    /**
     * @inheritDoc
     */
    public function objectRestore() {
        // TODO: Implement objectRestore() method.
    }

    /**
     * @inheritDoc
     */
    public function beforeUse(): bool {
        // TODO: Implement beforeUse() method.
    }

    public function fetch(): ?array {
        // TODO: Implement fetch() method.
    }

    public function fetchAll(): ?array {
        // TODO: Implement fetchAll() method.
    }

    public function nextResult(): ?bool {
        // TODO: Implement nextResult() method.
    }

    public function prepare(string $sql, array $data): ?Statement {
        // TODO: Implement prepare() method.
    }
}
