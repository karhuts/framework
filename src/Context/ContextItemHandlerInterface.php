<?php
declare(strict_types=1);
namespace Karthus\Context;

interface ContextItemHandlerInterface {
    function onContextCreate();
    function onDestroy($context);
}
