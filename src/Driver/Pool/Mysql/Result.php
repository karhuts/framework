<?php
declare(strict_types=1);
namespace Karthus\Driver\Pool\Mysql;

class Result {
    private $lastInsertId;
    private $result;
    private $lastError;
    private $lastErrorNo;
    private $affectedRows;
    private $totalCount = 0;


    /**
     * @return mixed
     */
    public function getLastInsertId(){
        return $this->lastInsertId;
    }

    /**
     * @param mixed $lastInsertId
     */
    public function setLastInsertId($lastInsertId) {
        $this->lastInsertId = $lastInsertId;
    }

    /**
     * @return mixed
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * @param mixed $lastError
     */
    public function setLastError($lastError){
        $this->lastError = $lastError;
    }

    /**
     * @return mixed
     */
    public function getLastErrorNo() {
        return $this->lastErrorNo;
    }

    /**
     * @param mixed $lastErrorNo
     */
    public function setLastErrorNo($lastErrorNo){
        $this->lastErrorNo = $lastErrorNo;
    }

    /**
     * @return mixed
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * @return array
     */
    public function getResultOne(): array {
        return $this->result[0] ?? [];
    }

    /**
     * @param mixed $result
     */
    public function setResult($result){
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getAffectedRows() {
        return $this->affectedRows;
    }

    /**
     * @param mixed $affectedRows
     */
    public function setAffectedRows($affectedRows){
        $this->affectedRows = $affectedRows;
    }

    /**
     * @return int
     */
    public function getTotalCount(): int {
        return $this->totalCount;
    }

    /**
     * @param int $totalCount
     */
    public function setTotalCount(int $totalCount){
        $this->totalCount = $totalCount;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'lastInsertId' => $this->lastInsertId,
            'result'       => $this->result,
            'lastError'    => $this->lastError,
            'lastErrorNo'  => $this->lastErrorNo,
            'affectedRows' => $this->affectedRows,
            'totalCount'   => $this->totalCount
        ];
    }
}
