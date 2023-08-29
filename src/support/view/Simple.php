<?php
declare(strict_types=1);

namespace karthus\support\view;

use karthus\support\view\Simple\View;
use karthus\ViewInterface;
use function karthus\request;
use function karthus\config;

class Simple implements ViewInterface {
    /**
     * @var array
     */
    protected static array $vars = [];


    /**
     * Assign.
     * @param array|string $name
     * @param mixed|null $value
     */
    public static function assign(array|string $name, mixed $value = null): void
    {
        static::$vars = array_merge(static::$vars, is_array($name) ? $name : [$name => $value]);
    }

    public static function render(string $template, array $vars): string
    {
        $request = request();
        $options = config("view.options", []);

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

