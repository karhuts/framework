<?php
declare(strict_types=1);
namespace Karthus\Task\AbstractInterface;

use Karthus\Task\Package;

interface TaskQueueInterface {
    public function pop():?Package;
    public function push(Package $package):bool ;
}
