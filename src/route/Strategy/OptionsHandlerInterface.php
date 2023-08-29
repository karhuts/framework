<?php
declare(strict_types=1);

namespace karthus\route\Strategy;

interface OptionsHandlerInterface
{
    public function getOptionsCallable(array $methods): callable;
}
