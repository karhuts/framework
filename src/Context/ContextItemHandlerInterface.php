<?php
declare(strict_types=1);
namespace Karthus\Context;

interface ContextItemHandlerInterface {
    public function onContextCreate();
    public function onDestroy($context);
}
