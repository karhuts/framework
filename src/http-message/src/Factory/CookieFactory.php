<?php
declare(strict_types=1);

namespace Karthus\Http\Message\Factory;
use Karthus\Http\Message\Cookie\Cookie;

/**
 * Class CookieFactory
 *
 * @package Karthus\Http\Message\Factory
 */
class CookieFactory {

    /**
     * Create cookie
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     * @return Cookie
     */
    public function createCookie(string $name, string $value = '', int $expire = 0): Cookie {
        return new Cookie($name, $value, $expire);
    }
}
