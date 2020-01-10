<?php
declare(strict_types=1);

namespace Karthus\Traits;

use Karthus\Service\Context;

trait StaticInstance {
    protected $instanceKey;

    /**
     * @param array $params
     * @param bool  $refresh
     * @return static|null
     */
    public static function instance($params = [], $refresh = false) {
        $key = get_called_class();
        $instance = null;
        if (Context::has($key)) {
            $instance = Context::get($key);
        }
        if ($refresh || is_null($instance) || ! $instance instanceof static) {
            $instance = new static(...$params);
            Context::set($key, $instance);
        }
        return $instance;
    }
}
