<?php
declare(strict_types=1);
namespace Karthus\PHPUnit;

use Karthus\Core;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase{

    public function runTest() {
        Core::getInstance()->initialize();
        return parent::runTest();
    }
}
