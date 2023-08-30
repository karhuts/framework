<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\support\view;

use karthus\support\view\Simple\View;
use karthus\ViewInterface;

use function karthus\config;
use function karthus\request;

class Simple implements ViewInterface
{
    protected static array $vars = [];

    /**
     * Assign.
     */
    public static function assign(array|string $name, mixed $value = null): void
    {
        static::$vars = array_merge(static::$vars, is_array($name) ? $name : [$name => $value]);
    }

    public static function render(string $template, array $vars): string
    {
        $request = request();
        $options = config('view.options', []);

        $engine = new View($options);
        $vars = array_merge(static::$vars, $vars);
        foreach ($vars as $key => $item) {
            $engine->assign($key, $item);
        }
        $content = $engine->display($template);
        static::$vars = [];
        return $content;
    }
}
