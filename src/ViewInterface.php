<?php
declare(strict_types=1);
namespace karthus;

interface ViewInterface
{
    /**
     * Render.
     * @param string $template
     * @param array $vars
     * @return string
     */
    public static function render(string $template, array $vars): string;
}
