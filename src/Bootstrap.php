<?php
declare(strict_types=1);

namespace karthus;

interface Bootstrap {
    /**
     * @return void
     */
    public static function run(): void;
}
